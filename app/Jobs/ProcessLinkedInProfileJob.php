<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserCredential;
use App\Services\Discovery\VertexAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Background Job: Proses sinkronisasi profil LinkedIn user DARI APIFY DATASET.
 *
 * Alur kerja:
 * 1. Dipanggil oleh LinkedInSyncController dengan userID dan linkedinUrl
 * 2. Fetch data profiling dari Proxycurl API
 * 3. Generate biografi profesional via Gemini AI
 * 4. Update tabel users (full_name, avatar_url, headline→position, bio)
 * 5. Upsert tabel user_credentials (experience, education — default [] jika kosong)
 * 6. Invalidate Redis cache match score agar dihitung ulang
 */
class ProcessLinkedInProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 2;
    public int $backoff = 10;

    protected string $userId;
    protected string $linkedinUrl;

    public function __construct(string $userId, string $linkedinUrl)
    {
        $this->userId = $userId;
        $this->linkedinUrl = $linkedinUrl;
    }

    public function handle(VertexAiService $vertexAi): void
    {
        Log::info("ProcessLinkedInProfileJob: Mulai fetch Proxycurl dataset.", [
            'user_id'      => $this->userId,
            'linkedin_url' => $this->linkedinUrl,
        ]);

        $user = User::find($this->userId);
        if (!$user) {
            Log::error("ProcessLinkedInProfileJob: User tidak ditemukan.", ['user_id' => $this->userId]);
            return;
        }

        // ── Step 1: Fetch Official LinkedIn API (OAuth 2.0) ─────────────────
        $accessToken = env('API_OAUTH_LINKEDIN');
        if (!$accessToken) {
            Log::error("ProcessLinkedInProfileJob: API_OAUTH_LINKEDIN token is missing in .env.");
            $this->fail(new \Exception("API_OAUTH_LINKEDIN is missing."));
            return;
        }

        $linkedInApi = app(\App\Services\LinkedInApiService::class);

        try {
            $profile     = $linkedInApi->fetchProfile($accessToken);
            $experiences = $linkedInApi->fetchExperience($accessToken);
            $educations  = $linkedInApi->fetchEducation($accessToken);
            
            Log::info("ProcessLinkedInProfileJob: Berhasil fetch data dari Official LinkedIn API.");
        } catch (\Exception $e) {
            Log::error("ProcessLinkedInProfileJob: Gagal fetch data dari Official LinkedIn API.", [
                'error' => $e->getMessage()
            ]);
            $this->fail($e);
            return;
        }

        // Simpan raw data untuk keperluan debugging/database
        $personData = [
            'profile'    => $profile,
            'experience' => $experiences,
            'education'  => $educations,
        ];

        // ATURAN KETAT: jangan null.
        $experiences = is_array($experiences) ? $experiences : [];
        $educations  = is_array($educations)  ? $educations  : [];

        // ── Step 3: Generate biografi profesional via Gemini AI ───────
        $headline = $profile['headline'] ?? ($user->position ?? '');
        $bio      = '';

        if (!empty($headline)) {
            $bio = $vertexAi->generateLinkedInBio($headline, $experiences);
        }

        // ── Step 4: Update tabel users ────────────────────────────────
        try {
            $updateData = [];
            
            $fullName = $profile['full_name'] ?? '';

            if (!empty($fullName)) {
                $updateData['name'] = $fullName;
            }
            if (!empty($profile['avatar_url'])) {
                $updateData['avatar_url'] = $profile['avatar_url'];
            }
            if (!empty($headline)) {
                $updateData['position'] = $headline;
            }
            if (!empty($bio)) {
                $updateData['bio'] = $bio;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }
            
            Log::info("ProcessLinkedInProfileJob: Tabel users berhasil diupdate.", ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error("ProcessLinkedInProfileJob: Gagal update tabel users.", ['error' => $e->getMessage()]);
            $this->fail($e);
            return;
        }

        // ── Step 5: Upsert tabel user_credentials ────────────────────
        try {
            UserCredential::updateOrCreate(
                ['user_id'  => $user->id, 'provider' => 'linkedin'],
                [
                    'experience' => $experiences,
                    'education'  => $educations,
                    'raw_data'   => $personData,
                ]
            );
            Log::info("ProcessLinkedInProfileJob: Tabel user_credentials berhasil di-upsert.", ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error("ProcessLinkedInProfileJob: Gagal upsert user_credentials.", ['error' => $e->getMessage()]);
        }

        // ── Step 6: Invalidate Redis Cache Match Score ────────────────
        try {
            $cacheKey = "connectx:match_score:{$user->id}";
            Cache::forget($cacheKey);
            Log::info("ProcessLinkedInProfileJob: Cache match score di-invalidate.", ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::warning("ProcessLinkedInProfileJob: Gagal invalidate cache.", ['error' => $e->getMessage()]);
        }

        Log::info("ProcessLinkedInProfileJob: Selesai! LinkedIn sync via Proxycurl sukses.");
    }
}
