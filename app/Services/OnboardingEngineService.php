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
        // Batalkan sesi yang sudah ada dan masih berjalan (jika ada)
        OnboardingSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'cancelled']);

        $entryFlow = OnboardingFlow::where('is_entry', true)->firstOrFail();
        $firstStep = $entryFlow->steps()->orderBy('order_index')->firstOrFail();

        $session = OnboardingSession::create([
            'id' => 'ses_' . Str::random(10),
            'user_id' => $user->id,
            'current_step_id' => $firstStep->id,
            'status' => 'in_progress',
        ]);

        return $session;
    }

    /**
     * Mengambil data langkah secara menyeluruh berserta relasinya (questions & options).
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

        // Menghitung progress (versi simplifikasi linear, karena kalkulasi jarak graf sangat kompleks)
        $progress = $this->calculateProgress($session);

        return [
            'id' => $step->id,
            'flow_key' => $step->flow_id,
            'section' => $step->section,
            'title' => $step->title,
            'subtitle' => $step->subtitle,
            'overall_progress' => $progress,
            'questions' => $step->questions,
            'cta' => [
                'label' => $step->cta_label,
                'enabled_when' => 'valid'
            ],
            'can_go_back' => $step->can_go_back
        ];
    }

    /**
     * Memvalidasi jawaban secara dinamis berdasarkan aturan pertanyaan dari database.
     * Akan melemparkan ValidationException jika ada yang tidak sesuai standar Frontend.
     */
    private function validateAnswers(string $stepId, array $answers): void
    {
        $step = OnboardingStep::with('questions')->findOrFail($stepId);
        $errors = [];

        foreach ($step->questions as $question) {
            $value = $answers[$question->id] ?? null;

            // 1. Pengecekan Aturan Wajib (Required)
            if ($question->required) {
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $errors[$question->id][] = "Bagian '{$question->label}' wajib untuk diisi.";
                    continue; // Skip hitungan aturan lanjut kalau datanya udah pasti kosong
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
                        // Jika input tipe biasa (Teks/Nomor) -> Kita cek panjang string nya
                        $strValue = (string) $value;
                        if (isset($rules['min_length']) && mb_strlen($strValue) < $rules['min_length']) {
                            $errors[$question->id][] = "'{$question->label}' terlalu pendek (Minimum {$rules['min_length']} huruf).";
                        }
                        if (isset($rules['max_length']) && mb_strlen($strValue) > $rules['max_length']) {
                            $errors[$question->id][] = "'{$question->label}' terlalu panjang (Maksimum {$rules['max_length']} huruf).";
                        }
                    } else {
                        // Jika input array (Multi-Select/Chips) -> Kita cek jumlah pilihan kotanya
                        $count = count($value);
                        if (isset($rules['min_selections']) && $count < $rules['min_selections']) {
                            $errors[$question->id][] = "Anda harus mencentang minimum {$rules['min_selections']} buah pada pilihan '{$question->label}'.";
                        }
                        if (isset($rules['max_selections']) && $count > $rules['max_selections']) {
                            $errors[$question->id][] = "Anda mencentang terlalu banyak! Maksimum {$rules['max_selections']} buah pada pilihan '{$question->label}'.";
                        }
                    }
                }
            }
        }

        // Kalau ada satu saja yang melanggar hukum form, Gagalkan dengan status code 422!
        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    /**
     * Memproses jawaban dari frontend dan menghitung langkah berikutnya.
     */
    public function processAnswer(OnboardingSession $session, string $stepId, array $answers): array
    {
        // 1. Tembok Pengaman Penangkis Hacker / Manipulasi Bypass Postman API.
        $this->validateAnswers($stepId, $answers);

        // 2. Simpan jawaban Murni.
        foreach ($answers as $questionId => $value) {
            OnboardingResponse::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'step_id' => $stepId,
                    'question_id' => $questionId
                ],
                [
                    'value' => is_array($value) ? $value : [$value], // memastikan struktur JSON konsisten
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
                return is_array($actualValue) && in_array($expectedValue, $actualValue);
            case 'contains':
                return is_array($actualValue) && in_array($expectedValue, $actualValue);
            default:
                return false;
        }
    }

    public function goBack(OnboardingSession $session): ?array
    {
        // Cara termudah untuk kembali ke langkah sebelumnya adalah dengan mengecek jawaban terakhir.
        $lastResponse = OnboardingResponse::where('session_id', $session->id)
            ->orderBy('answered_at', 'desc')
            ->first();

        if (!$lastResponse) {
            return null; // Tidak dapat mundur lebih jauh lagi
        }

        $previousStepId = $lastResponse->step_id;

        // Menghapus rekapan jawaban di langkah yang sedang berjalan, seolah-olah "dibatalkan"
        OnboardingResponse::where('session_id', $session->id)
            ->where('step_id', $session->current_step_id)
            ->delete();

        $session->update(['current_step_id' => $previousStepId]);

        return [
            'next_step' => $this->getCurrentStep($session),
            'progress' => $this->calculateProgress($session),
            'can_go_back' => true,
        ];
    }

    private function calculateProgress(OnboardingSession $session): array
    {
        // Mockup Progress (Progres semu). Dibuat statis dengan asumsi 12 langkah berdasarkan Data Kontrak.
        $answeredSteps = OnboardingResponse::where('session_id', $session->id)->distinct('step_id')->count('step_id');
        return [
            'current' => $answeredSteps + 1,
            'total' => 12 // Hardcoded sementara berdasarkan gambaran API Contract loo
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
