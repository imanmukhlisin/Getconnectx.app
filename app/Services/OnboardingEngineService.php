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

        $entryFlow = OnboardingFlow::where('is_entry', true)->firstOrFail();
        $firstStep = $entryFlow->steps()->orderBy('order_index')->firstOrFail();

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

        // Menghitung progress
        $progress = $this->calculateProgress($session);
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
        $existingAnswers = OnboardingResponse::where('session_id', $session->id)
            ->where('step_id', $step->id)
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
    private function validateAnswers(string $stepId, array $answers): void
    {
        $step = OnboardingStep::with('questions')->findOrFail($stepId);
        $errors = [];
        $locale = app()->getLocale();

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
                    $depAnswer = $answers[$depQuestionId] ?? null;
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
        // 1. Tembok Pengaman: Validasi input berdasarkan aturan pertanyaan
        $this->validateAnswers($stepId, $answers);

        // 2. Simpan jawaban (upsert per question_id agar tidak duplikat saat back-and-forth)
        foreach ($answers as $questionId => $value) {
            OnboardingResponse::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'step_id' => $stepId,
                    'question_id' => $questionId
                ],
                [
                    'value' => is_array($value) ? $value : [$value],
                    'answered_at' => now(),
                ]
            );
        }

        $currentStep = OnboardingStep::findOrFail($stepId);

        // Mencari langkah selanjutnya (Branching Logic)
        $nextStep = $this->determineNextStep($currentStep, $session);

        if (!$nextStep) {
            // Tidak ada langkah selanjutnya = Alur (Flow) Selesai
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->mapResponsesToProfile($session);

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
            'progress' => $this->calculateProgress($session),
            'can_go_back' => $nextStep->can_go_back,
        ];
    }

    /**
     * Menentukan langkah selanjutnya dengan mengevaluasi transisi (transitions).
     */
    private function determineNextStep(OnboardingStep $currentStep, OnboardingSession $session): ?OnboardingStep
    {
        $transitions = $currentStep->transitions()->orderByDesc('priority')->get();

        foreach ($transitions as $transition) {
            if ($this->evaluateCondition($transition->condition, $session)) {
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
    private function evaluateCondition(?array $condition, OnboardingSession $session): bool
    {
        if (empty($condition)) {
            return true; // Transisi tanpa syarat (unconditional)
        }

        $questionId = $condition['question_id'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $expectedValue = $condition['value'] ?? null;

        if (!$questionId) return false;

        $response = OnboardingResponse::where('session_id', $session->id)
            ->where('question_id', $questionId)
            ->first();

        // Jika pertanyaan tersebut belum dijawab
        if (!$response) return false;

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

    private function calculateProgress(OnboardingSession $session): array
    {
        $answeredSteps = OnboardingResponse::where('session_id', $session->id)
            ->distinct('step_id')
            ->count('step_id');

        return [
            'current' => $answeredSteps + 1,
            'total' => 12 // Estimasi statis berdasarkan gambaran API Contract
        ];
    }

    /**
     * Memetakan kumpulan jawaban JSON ke kolom tabel `users` utama dan sinkronisasi tags.
     */
    private function mapResponsesToProfile(OnboardingSession $session)
    {
        $user = $session->user;
        $responses = OnboardingResponse::where('session_id', $session->id)->get()->keyBy('question_id');

        $updateData = [];
        $syncTags = [];

        // Contoh aturan sinkronisasi berdasarkan ID yang disyaratkan di Seeder
        if ($responses->has('q_first_name')) {
            $firstName = $this->getValue($responses['q_first_name']->value);
            $lastName = $responses->has('q_last_name') ? $this->getValue($responses['q_last_name']->value) : '';
            $updateData['name'] = trim($firstName . ' ' . $lastName);
        }

        if ($responses->has('q_use_connectx')) {
            $action = $this->getValue($responses['q_use_connectx']->value);
            if ($action === 'startup') {
                $updateData['role_category'] = 'Startup';
            } elseif ($action === 'founder') {
                $updateData['role_category'] = 'Founder';
            } elseif ($action === 'cofounder') {
                $updateData['role_category'] = 'Co-Founder';
            } elseif ($action === 'team') {
                $updateData['role_category'] = 'Team Member';
            }
        }

        if ($responses->has('q_availability')) {
            $updateData['commitment_level'] = $this->getValue($responses['q_availability']->value);
        }

        if ($responses->has('q_ff_stage')) {
            $updateData['startup_stage'] = $this->getValue($responses['q_ff_stage']->value);
        }

        $updateData['is_onboarded'] = true;

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        // Sinkronisasi Many-to-Many Tags (Industri dan Keahlian/Skill)
        $tagNames = [];

        if ($responses->has('q_industry')) {
            $names = $responses['q_industry']->value;
            if (is_array($names)) $tagNames = array_merge($tagNames, $names);
        }

        if ($responses->has('q_ff_ind')) {
            $names = $responses['q_ff_ind']->value;
            if (is_array($names)) $tagNames = array_merge($tagNames, $names);
        }

        if ($responses->has('q_flow_e_skill')) {
            $names = $responses['q_flow_e_skill']->value;
            if (is_array($names)) $tagNames = array_merge($tagNames, $names);
        }

        if (!empty($tagNames)) {
            // Dapatkan tag IDs dari database berdasarkan nama karena form nyimpan 'name'
            $tagIds = \App\Models\Tag::whereIn('name', $tagNames)->pluck('id')->toArray();
            if (!empty($tagIds)) {
                $user->tags()->sync($tagIds);
            }
        }

        // Cache Invalidation for Feed: When user's profile changes (role, stages, tags),
        // we must clear their discovery feed cache so new compatibility scores apply.
        app(\App\Services\FeedService::class)->invalidateUserFeedCache($user->id);
    }

    private function getValue($jsonValue)
    {
        if (is_array($jsonValue)) {
            return $jsonValue[0] ?? null;
        }
        return $jsonValue;
    }
}
