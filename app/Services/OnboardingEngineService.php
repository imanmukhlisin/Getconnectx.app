<?php

namespace App\Services;

use App\Models\Onboarding\OnboardingFlow;
use App\Models\Onboarding\OnboardingSession;
use App\Models\Onboarding\OnboardingResponse;
use App\Models\Onboarding\OnboardingStep;
use App\Models\User;
use Illuminate\Support\Str;

class OnboardingEngineService
{
    /**
     * Memulai sesi onboarding baru untuk pengguna.
     */
    public function startSession(User $user): OnboardingSession
    {
        // Cek apakah ada sesi yang masih in_progress → kembalikan saja
        $existingSession = OnboardingSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if ($existingSession) {
            return $existingSession;
        }

        // Eager-load entry flow + first step in ONE query
        $entryFlow = OnboardingFlow::where('is_entry', true)
            ->with(['steps' => fn($q) => $q->orderBy('order_index')->limit(1)])
            ->firstOrFail();

        $firstStep = $entryFlow->steps->first();
        if (!$firstStep) {
            throw new \RuntimeException('Entry flow has no steps configured.');
        }

        $session = OnboardingSession::create([
            'id' => 'ses_' . Str::random(10),
            'user_id' => $user->id,
            'current_step_id' => $firstStep->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $session;
    }

    /**
     * Mengambil data langkah secara menyeluruh berserta relasinya (questions & options).
     * Output format sudah sesuai dengan API Contract yang disepakati dengan FE.
     */
    public function getCurrentStep(OnboardingSession $session): array
    {
        $step = $session->currentStep()->with([
            'questions' => function ($q) {
                $q->orderBy('order_index');
            },
            'questions.options' => function ($q) {
                $q->orderBy('order_index');
            }
        ])->firstOrFail();

        // Hanya 1 kali query ke onboarding_responses (untuk progress, existing answers, dll)
        $allResponses = OnboardingResponse::where('session_id', $session->id)->get();

        // Menghitung progress
        $progress = $this->calculateProgress($session, $allResponses);
        $sectionProgress = $this->calculateSectionProgress($step);

        // Translasi On-the-fly untuk Server Driven UI berdasarkan Locale
        $locale = app()->getLocale();
        $gT = function ($field) use ($locale) {
            if (is_array($field)) {
                return $field[$locale] ?? $field['en'] ?? reset($field);
            }
            return $field;
        };

        // Ambil jawaban yang sudah ada untuk step ini (prefill pada resume/back)
        $existingAnswers = $allResponses->where('step_id', $step->id)
            ->pluck('value', 'question_id')
            ->toArray();

        $mappedQuestions = $step->questions->map(function ($q) use ($gT, $existingAnswers) {
            $qArr = [
                'id' => $q->id,
                'type' => $q->type,
                'label' => $gT($q->label),
                'sub_label' => $gT($q->sub_label),
                'helper_text' => $gT($q->helper_text),
                'placeholder' => $gT($q->placeholder),
                'required' => $q->required,
                'validation' => $q->validation,
                'depends_on' => $q->depends_on,
                'meta' => $q->meta,
            ];

            // Sertakan jawaban sebelumnya jika ada (untuk resume/back)
            if (isset($existingAnswers[$q->id])) {
                $val = $existingAnswers[$q->id];
                // Jika value array dengan 1 elemen dan bukan multi-select, flatten
                if (is_array($val) && count($val) === 1 && !str_contains($q->type, 'multi')) {
                    $qArr['previous_answer'] = $val[0];
                } else {
                    $qArr['previous_answer'] = $val;
                }
            }

            if ($q->relationLoaded('options') && $q->options->isNotEmpty()) {
                $qArr['options'] = $q->options->map(function ($opt) use ($gT) {
                    return [
                        'id' => $opt->id,
                        'label' => $gT($opt->label),
                        'sub_label' => $gT($opt->sub_label),
                        'value' => $opt->value,
                        'icon' => $opt->icon,
                        'group' => $opt->group_name,
                    ];
                })->toArray();
            } else {
                $qArr['options'] = [];
            }
            return $qArr;
        });

        return [
            'id' => $step->id,
            'flow_key' => $step->flow_id,
            'section' => $step->section,
            'section_progress' => $sectionProgress,
            'overall_progress' => $progress,
            'title' => $gT($step->title),
            'subtitle' => $gT($step->subtitle),
            'questions' => $mappedQuestions,
            'cta' => [
                'label' => (function ($v) { return !empty($v) ? $v : 'Continue'; })($gT($step->cta_label)),
                'enabled_when' => 'valid'
            ],
            'can_go_back' => $step->can_go_back,
            'auto_advance' => $step->auto_advance,
        ];
    }

    /**
     * Menghitung section_progress berdasarkan posisi step di flow (e.g. "2/4")
     */
    private function calculateSectionProgress(OnboardingStep $step): string
    {
        $sameSection = OnboardingStep::where('flow_id', $step->flow_id)
            ->where('section', $step->section)
            ->orderBy('order_index')
            ->pluck('id')
            ->toArray();

        $position = array_search($step->id, $sameSection);
        $position = ($position !== false) ? $position + 1 : 1;
        $total = count($sameSection);

        return "{$position}/{$total}";
    }

    /**
     * Memvalidasi jawaban secara dinamis berdasarkan aturan pertanyaan dari database.
     * Akan melemparkan ValidationException jika ada yang tidak sesuai standar Frontend.
     * Pesan error dilokalisasi berdasarkan Accept-Language header (id/en).
     */
    private function validateAnswersByStep(OnboardingStep $step, array $answers, ?OnboardingSession $session = null): void
    {
        $errors = [];
        $locale = app()->getLocale();

        // Load prior responses once (for cross-step depends_on lookups)
        $priorResponses = null;
        if ($session) {
            $priorResponses = OnboardingResponse::where('session_id', $session->id)
                ->get()
                ->keyBy('question_id');
        }

        foreach ($step->questions as $question) {
            $value = $answers[$question->id] ?? null;
            $labelText = is_array($question->label) ? ($question->label[$locale] ?? $question->label['en'] ?? '') : $question->label;

            // Cek depends_on: jika pertanyaan ini bergantung pada jawaban lain
            // dan kondisinya tidak terpenuhi, skip validasi (pertanyaan hidden)
            if (!empty($question->depends_on)) {
                $depQuestionId = $question->depends_on['question_id'] ?? null;
                $depOperator = $question->depends_on['operator'] ?? 'equals';
                $depValue = $question->depends_on['value'] ?? null;

                if ($depQuestionId) {
                    // Check current step answers first, then fall back to prior session responses
                    $depAnswer = $answers[$depQuestionId] ?? null;
                    if ($depAnswer === null && $priorResponses && isset($priorResponses[$depQuestionId])) {
                        $depAnswer = $this->getValue($priorResponses[$depQuestionId]->value);
                    }
                    $shouldShow = $this->evaluateDependsOn($depAnswer, $depOperator, $depValue);
                    if (!$shouldShow) {
                        continue; // Pertanyaan ini tersembunyi, skip validasi
                    }
                }
            }

            // 1. Pengecekan Aturan Wajib (Required)
            if ($question->required) {
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $errors[$question->id][] = $locale === 'id'
                        ? "'{$labelText}' wajib untuk diisi."
                        : "'{$labelText}' is required.";
                    continue;
                }
            }

            // Jika jawaban tidak diisi dan statusnya opsional, biarkan lolos.
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }

            // 2. Pengecekan Ekstra (JSON Validation Rule: Length / Count Selection)
            if (!empty($question->validation)) {
                $rules = is_string($question->validation) ? json_decode($question->validation, true) : $question->validation;
                
                if (is_array($rules)) {
                    if (!is_array($value)) {
                        // Jika input tipe biasa (Teks/Nomor) → Cek panjang string
                        $strValue = (string) $value;
                        if (isset($rules['min_length']) && mb_strlen($strValue) < $rules['min_length']) {
                            $errors[$question->id][] = $locale === 'id'
                                ? "'{$labelText}' terlalu pendek (Minimum {$rules['min_length']} huruf)."
                                : "'{$labelText}' is too short (Minimum {$rules['min_length']} characters).";
                        }
                        if (isset($rules['max_length']) && mb_strlen($strValue) > $rules['max_length']) {
                            $errors[$question->id][] = $locale === 'id'
                                ? "'{$labelText}' terlalu panjang (Maksimum {$rules['max_length']} huruf)."
                                : "'{$labelText}' is too long (Maximum {$rules['max_length']} characters).";
                        }
                    } else {
                        // Jika input array (Multi-Select/Chips) → Cek jumlah pilihan
                        $count = count($value);
                        if (isset($rules['min_selections']) && $count < $rules['min_selections']) {
                            $errors[$question->id][] = $locale === 'id'
                                ? "Anda harus mencentang minimum {$rules['min_selections']} buah pada pilihan '{$labelText}'."
                                : "You must select at least {$rules['min_selections']} items for '{$labelText}'.";
                        }
                        if (isset($rules['max_selections']) && $count > $rules['max_selections']) {
                            $errors[$question->id][] = $locale === 'id'
                                ? "Anda mencentang terlalu banyak! Maksimum {$rules['max_selections']} buah pada pilihan '{$labelText}'."
                                : "Too many selections! Maximum {$rules['max_selections']} items for '{$labelText}'.";
                        }
                    }
                }
            }
        }

        // Kalau ada satu saja yang melanggar, Gagalkan dengan status code 422!
        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    /**
     * Evaluasi depends_on condition within a step (for conditional rendering).
     */
    private function evaluateDependsOn($actualValue, string $operator, $expectedValue): bool
    {
        if ($actualValue === null) return false;

        switch ($operator) {
            case 'equals':
                return $actualValue == $expectedValue;
            case 'not_equals':
                return $actualValue != $expectedValue;
            case 'in':
                return is_array($expectedValue) ? in_array($actualValue, $expectedValue) : $actualValue == $expectedValue;
            default:
                return false;
        }
    }

    /**
     * Memproses jawaban dari frontend dan menghitung langkah berikutnya.
     */
    public function processAnswer(OnboardingSession $session, string $stepId, array $answers): array
    {
        // 0. Ambil current step dengan relasi questions (Cuma 1 Query)
        $currentStep = OnboardingStep::with('questions')->findOrFail($stepId);

        // 1. Tembok Pengaman: Validasi input berdasarkan aturan pertanyaan
        $this->validateAnswersByStep($currentStep, $answers, $session);

        // 2. Simpan jawaban (upsert per question_id agar tidak duplikat saat back-and-forth)
        foreach ($answers as $questionId => $value) {
            OnboardingResponse::updateOrCreate(
                [
                    'session_id'  => $session->id,
                    'step_id'     => $stepId,
                    'question_id' => $questionId,
                ],
                [
                    'value'       => is_array($value) ? $value : [$value],
                    'answered_at' => now(),
                ]
            );
        }

        // Hit ke database SEKALI SAJA di sini untuk semua jawaban di session ini. Menghindari N+1 Query.
        $allResponses = OnboardingResponse::where('session_id', $session->id)->get();
        $responsesByKey = $allResponses->keyBy('question_id');

        // Mencari langkah selanjutnya (Branching Logic) - Kirim dictionary di memory
        $nextStep = $this->determineNextStep($currentStep, $session, $responsesByKey);

        if (!$nextStep) {
            // Tidak ada langkah selanjutnya = Alur (Flow) Selesai
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Dispatch profile mapping to queue (non-blocking)
            dispatch(function () use ($session) {
                $this->mapResponsesToProfile($session);
            })->afterResponse();

            return [
                'next_step' => null,
                'completed' => true,
                'profile_id' => $session->user_id,
                'redirect_to' => '/home',
            ];
        }

        // Update 'current_step_id' di database sesi
        $session->update(['current_step_id' => $nextStep->id]);

        return [
            'next_step' => $this->getCurrentStep($session),
            'progress' => $this->calculateProgress($session, $allResponses),
            'can_go_back' => $nextStep->can_go_back,
        ];
    }

    /**
     * Menentukan langkah selanjutnya dengan mengevaluasi transisi (transitions).
     */
    private function determineNextStep(OnboardingStep $currentStep, OnboardingSession $session, $responsesByKey = null): ?OnboardingStep
    {
        $transitions = $currentStep->transitions()->orderByDesc('priority')->get();

        if ($responsesByKey === null && $transitions->isNotEmpty()) {
            $responsesByKey = OnboardingResponse::where('session_id', $session->id)->get()->keyBy('question_id');
        }

        foreach ($transitions as $transition) {
            if ($this->evaluateCondition($transition->condition, $responsesByKey)) {
                if ($transition->to_step_id) {
                    return OnboardingStep::find($transition->to_step_id);
                } elseif ($transition->to_flow_id) {
                    // Loncat ke Alur Lain (Flow Jump)
                    $flow = OnboardingFlow::find($transition->to_flow_id);
                    return $flow->steps()->orderBy('order_index')->first();
                }
            }
        }

        // Progres linear secara default (jika tidak ada kondisi yang terpenuhi)
        return OnboardingStep::where('flow_id', $currentStep->flow_id)
            ->where('order_index', '>', $currentStep->order_index)
            ->orderBy('order_index')
            ->first();
    }

    /**
     * Mengevaluasi kondisi bersyarat untuk penentuan cabang (branching logic).
     */
    private function evaluateCondition(?array $condition, $responsesByKey = null): bool
    {
        if (empty($condition)) {
            return true; // Transisi tanpa syarat (unconditional)
        }

        $questionId = $condition['question_id'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $expectedValue = $condition['value'] ?? null;

        if (!$questionId || !$responsesByKey || !isset($responsesByKey[$questionId])) return false;

        $response = $responsesByKey[$questionId];

        $actualValue = $response->value; // biasanya disimpan dalam bentuk array, contoh: ["founder"]

        // Jika disimpan sebagai array, ambil elemen pertama
        if (is_array($actualValue) && count($actualValue) === 1) {
            $actualValue = $actualValue[0];
        }

        switch ($operator) {
            case 'equals':
                return $actualValue == $expectedValue;
            case 'not_equals':
                return $actualValue != $expectedValue;
            case 'in':
                if (is_array($actualValue)) {
                    return !empty(array_intersect((array) $expectedValue, $actualValue));
                }
                return in_array($actualValue, (array) $expectedValue);
            case 'not_in':
                if (is_array($actualValue)) {
                    return empty(array_intersect((array) $expectedValue, $actualValue));
                }
                return !in_array($actualValue, (array) $expectedValue);
            case 'contains':
                return is_array($actualValue) && in_array($expectedValue, $actualValue);
            case 'exists':
                return $actualValue !== null && $actualValue !== '' && $actualValue !== [];
            default:
                return false;
        }
    }

    public function goBack(OnboardingSession $session): ?array
    {
        // Cari jawaban terakhir yang dimiliki sebelum step saat ini
        $lastResponse = OnboardingResponse::where('session_id', $session->id)
            ->where('step_id', '!=', $session->current_step_id)
            ->orderBy('answered_at', 'desc')
            ->first();

        if (!$lastResponse) {
            return null; // Tidak dapat mundur lebih jauh lagi
        }

        $previousStepId = $lastResponse->step_id;

        // Hapus jawaban step saat ini (karena dibatalkan user)
        OnboardingResponse::where('session_id', $session->id)
            ->where('step_id', $session->current_step_id)
            ->delete();

        $session->update(['current_step_id' => $previousStepId]);

        // Cek apakah masih ada step yang bisa di-back lagi setelah ini
        $canGoBackFurther = OnboardingResponse::where('session_id', $session->id)
            ->where('step_id', '!=', $previousStepId)
            ->exists();

        return [
            'current_step' => $this->getCurrentStep($session),
            'progress' => $this->calculateProgress($session),
            'can_go_back' => $canGoBackFurther,
        ];
    }

    private function calculateProgress(OnboardingSession $session, $responses = null): array
    {
        if (!$responses) {
            $responses = OnboardingResponse::where('session_id', $session->id)->get();
        }

        // Only count prior steps (excluding the current step to avoid double counting on resumes)
        $answeredSteps = $responses->where('step_id', '!=', $session->current_step_id)
                                   ->pluck('step_id')
                                   ->unique()
                                   ->count();

        $responsesByKey = $responses->keyBy('question_id');

        $useConnectx = $responsesByKey['q_use_connectx'] ?? null;
        $bldType = $responsesByKey['q_bld_type'] ?? null;
        $fdrLooking = $responsesByKey['q_fdr_looking'] ?? null;

        $action = $useConnectx ? $this->getValue($useConnectx->value) : null;
        $subType = $bldType ? $this->getValue($bldType->value) : null;
        $fdrGoal = $fdrLooking ? $this->getValue($fdrLooking->value) : null;

        // Base Common = 5 steps
        if ($action === 'startup') {
            $total = 16; // common(5) + startup(3) + traction(1) + finish(4) + need(1) + end(2) = 16
        } else {
            $total = match ($subType) {
                'founder'   => ($fdrGoal === 'both') ? 15 : 14, // common(5) + builder(3) + founder(2) + cf/team(4) or both(5)
                'cofounder' => 14,  // common(5) + builder(3) + cofounder(6)
                'team'      => 14,  // common(5) + builder(3) + team(6)
                default     => 14,  // Default assumption
            };
        }

        // Safely bound the current step so it never exceeds total (UX safeguard)
        $current = $answeredSteps + 1;
        if ($current > $total && $session->status !== 'completed') {
            $total = $current;
        }

        return [
            'current' => $current,
            'total'   => $total,
        ];
    }

    /**
     * Memetakan kumpulan jawaban JSON ke kolom tabel `users` utama dan sinkronisasi tags.
     * Mendukung semua 18 flow dan 90+ question ID dari seeder.
     */
    private function mapResponsesToProfile(OnboardingSession $session)
    {
        $user = $session->user;
        $responses = OnboardingResponse::where('session_id', $session->id)->get()->keyBy('question_id');

        $updateData = [];

        // ── Nama ──
        if ($responses->has('q_first_name')) {
            $firstName = $this->getValue($responses['q_first_name']->value);
            $lastName = $responses->has('q_last_name') ? $this->getValue($responses['q_last_name']->value) : '';
            $updateData['name'] = trim($firstName . ' ' . $lastName);
        }

        // ── Tanggal Lahir ──
        if ($responses->has('q_dob')) {
            $updateData['date_of_birth'] = $this->getValue($responses['q_dob']->value);
        }

        // ── Lokasi ──
        if ($responses->has('q_location')) {
            $updateData['location'] = $this->getValue($responses['q_location']->value);
        }

        // ── Gender ──
        if ($responses->has('q_gender')) {
            $updateData['gender'] = $this->getValue($responses['q_gender']->value);
        }

        // ── Role Category ──
        if ($responses->has('q_use_connectx')) {
            $action = $this->getValue($responses['q_use_connectx']->value);
            if ($action === 'startup') {
                $updateData['role_category'] = 'Startup';
            } elseif ($action === 'builder' && $responses->has('q_bld_type')) {
                $subType = $this->getValue($responses['q_bld_type']->value);
                $updateData['role_category'] = match ($subType) {
                    'founder'   => 'Founder',
                    'cofounder' => 'Co-Founder',
                    'team'      => 'Team Member',
                    default     => null,
                };
            }
        }

        // ── Primary Role (Builder paths) ──
        if ($responses->has('q_bld_role')) {
            $updateData['primary_role'] = $this->getValue($responses['q_bld_role']->value);
        }

        // ── Years of Experience ──
        if ($responses->has('q_bld_years')) {
            $updateData['years_experience'] = $this->getValue($responses['q_bld_years']->value);
        }

        // ── Startup Experience Level ──
        $expQuestions = ['q_bld_exp_fdr', 'q_bld_exp_cf', 'q_bld_exp_tm'];
        foreach ($expQuestions as $qid) {
            if ($responses->has($qid)) {
                $updateData['startup_experience'] = $this->getValue($responses[$qid]->value);
                break;
            }
        }

        // ── Co-Founder Type (for co-founder joining path) ──
        if ($responses->has('q_cf_type')) {
            $updateData['cofounder_type'] = $this->getValue($responses['q_cf_type']->value);
        }

        // ── Commitment Level (multiple possible question IDs dari berbagai flow) ──
        $availQuestions = ['q_fdr_cf_avail','q_fdr_tm_avail','q_fdr_bt_avail','q_cf_avail','q_tm_avail'];
        foreach ($availQuestions as $qid) {
            if ($responses->has($qid)) {
                $updateData['commitment_level'] = $this->getValue($responses[$qid]->value);
                break;
            }
        }

        // ── Startup Commitment (startup path) ──
        if ($responses->has('q_su_commitment')) {
            $updateData['commitment_level'] = $this->getValue($responses['q_su_commitment']->value);
        }

        // ── LinkedIn (multiple possible question IDs) ──
        $linkedinUrlToSync = null;
        $linkedinQuestions = ['q_fdr_cf_linkedin','q_fdr_tm_linkedin','q_fdr_bt_linkedin','q_cf_linkedin','q_tm_linkedin','q_su_linkedin'];
        foreach ($linkedinQuestions as $qid) {
            if ($responses->has($qid)) {
                $val = $this->getValue($responses[$qid]->value);
                if (!empty($val)) {
                    $updateData['linkedin_url'] = $val;
                    $linkedinUrlToSync = $val;
                    break;
                }
            }
        }

        // ── Startup-specific fields ──
        if ($responses->has('q_su_name')) {
            $updateData['startup_name'] = $this->getValue($responses['q_su_name']->value);
        }
        if ($responses->has('q_su_tagline')) {
            $updateData['startup_tagline'] = $this->getValue($responses['q_su_tagline']->value);
        }
        if ($responses->has('q_su_stage')) {
            $updateData['startup_stage'] = $this->getValue($responses['q_su_stage']->value);
        }

        // ── Remote & Relocate preferences ──
        $remoteQuestions = ['q_open_remote','q_fdr_cf_remote','q_fdr_tm_remote','q_fdr_bt_remote','q_cf_remote','q_tm_remote'];
        foreach ($remoteQuestions as $qid) {
            if ($responses->has($qid)) {
                $updateData['open_to_remote'] = $this->getValue($responses[$qid]->value) === 'yes';
                break;
            }
        }
        $relocateQuestions = ['q_fdr_cf_relocate','q_fdr_tm_relocate','q_fdr_bt_relocate','q_cf_relocate','q_tm_relocate'];
        foreach ($relocateQuestions as $qid) {
            if ($responses->has($qid)) {
                $updateData['willing_to_relocate'] = $this->getValue($responses[$qid]->value) === 'yes';
                break;
            }
        }

        $updateData['is_onboarded'] = true;

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        // ── Buat atau Update Data di Tabel Startups ──
        if (isset($updateData['role_category']) && $updateData['role_category'] === 'Startup') {
            $startupData = [];
            
            if ($responses->has('q_su_name')) {
                $startupData['name'] = $this->getValue($responses['q_su_name']->value);
            }
            if ($responses->has('q_su_tagline')) {
                $startupData['tagline'] = $this->getValue($responses['q_su_tagline']->value);
            }
            if ($responses->has('q_su_stage')) {
                $startupData['stage'] = $this->getValue($responses['q_su_stage']->value);
            }
            if ($responses->has('q_su_industry')) {
                $industries = $responses['q_su_industry']->value;
                if (is_array($industries) && count($industries) > 0) {
                    $startupData['industry'] = $industries[0];
                    if (count($industries) > 1) {
                        $startupData['secondary_industry'] = $industries[1];
                    }
                }
            }

            if (isset($updateData['location'])) {
                $startupData['city'] = $updateData['location'];
            }
            
            $startupData['latitude'] = $user->latitude;
            $startupData['longitude'] = $user->longitude;

            // Pastikan startup_name wajib ada sebelum masuk tabel startups
            if (!empty($startupData['name'])) {
                \App\Models\Startup::updateOrCreate(
                    ['owner_id' => $user->id],
                    $startupData
                );
            }
        }

        // ── Buat atau Update Data di Tabel Builders (P2P Discovery) ──
        if (isset($updateData['role_category']) && $updateData['role_category'] !== 'Startup') {
            \App\Models\Builder::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'role_category'      => $updateData['role_category'] ?? null,
                    'primary_role'       => $updateData['primary_role'] ?? null,
                    'commitment_level'   => $updateData['commitment_level'] ?? null,
                    'work_arrangement'   => $user->work_arrangement ?? null,
                    'remote_ready'       => $updateData['open_to_remote'] ?? false,
                    'open_to_remote'     => $updateData['open_to_remote'] ?? false,
                    'willing_to_relocate' => $updateData['willing_to_relocate'] ?? false,
                ]
            );
        }


        // ── Sinkronisasi Many-to-Many Tags (Industri + Skill) ──
        $tagNames = [];

        // Semua kemungkinan industry question IDs
        $industryQids = ['q_fdr_industry','q_cf_industry','q_tm_industry','q_su_industry'];
        foreach ($industryQids as $qid) {
            if ($responses->has($qid)) {
                $names = $responses[$qid]->value;
                if (is_array($names)) $tagNames = array_merge($tagNames, $names);
            }
        }

        // Semua kemungkinan skill question IDs
        $skillQids = ['q_tm_skills','q_su_need_tm_skills','q_su_need_bt_tm'];
        foreach ($skillQids as $qid) {
            if ($responses->has($qid)) {
                $names = $responses[$qid]->value;
                if (is_array($names)) $tagNames = array_merge($tagNames, $names);
            }
        }

        if (!empty($tagNames)) {
            $tagIds = \App\Models\Tag::whereIn('name', $tagNames)->pluck('id')->toArray();
            if (!empty($tagIds)) {
                $user->tags()->sync($tagIds);
            }
        }

        // Cache Invalidation for Feed
        try {
            app(\App\Services\FeedService::class)->invalidateUserFeedCache($user->id);
        } catch (\Throwable $e) {
            // FeedService may not exist yet — silently ignore
        }

        // ── Dispatch LinkedIn Background Scraper (Serverless/Webhook strategy) ──
        if (!empty($linkedinUrlToSync)) {
            app(\App\Services\LinkedInScraperService::class)->triggerScrapeAsync($user, $linkedinUrlToSync);
        }
    }

    private function getValue($jsonValue)
    {
        if (is_array($jsonValue)) {
            return $jsonValue[0] ?? null;
        }
        return $jsonValue;
    }
}
