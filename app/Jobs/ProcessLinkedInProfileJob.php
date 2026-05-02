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
use Illuminate\Support\Facades\Log;

/**
 * Background Job: Sinkronisasi profil LinkedIn user menggunakan Vertex AI (Gemini).
 *
 * Alur kerja:
 * 1. Dipanggil oleh LinkedInSyncController dengan userId dan linkedinUrl.
 * 2. Minta VertexAiService untuk fetch HTML publik LinkedIn dan parsing via Gemini.
 * 3. Simpan data dasar (name, avatar, headline, bio) ke tabel users.
 * 4. Simpan riwayat experience & education ke tabel user_credentials.
 * 5. Invalidate Redis cache match score agar feed discovery tetap fresh.
 *
 * CATATAN QUEUE:
 * - Vercel (serverless)  : QUEUE_CONNECTION=sync  — job berjalan synchronous dalam request yang sama.
 * - VPS (main branch)    : QUEUE_CONNECTION=database — job berjalan di background via `php artisan queue:work`.
 */
class ProcessLinkedInProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum seconds this job is allowed to run.
     * Gemini parsing can take up to ~30 seconds, so we set a safe upper limit.
     */
    public int $timeout = 90;

    /** Retry once if the job fails (e.g. transient network error). */
    public int $tries = 2;

    /** Seconds to wait before the first retry. */
    public int $backoff = 15;

    protected string $userId;
    protected string $linkedinUrl;

    public function __construct(string $userId, string $linkedinUrl)
    {
        $this->userId      = $userId;
        $this->linkedinUrl = $linkedinUrl;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Main Handler
    // ═══════════════════════════════════════════════════════════════════════

    public function handle(VertexAiService $vertexAi): void
    {
        Log::info('ProcessLinkedInProfileJob: Starting LinkedIn sync via Vertex AI.', [
            'user_id'      => $this->userId,
            'linkedin_url' => $this->linkedinUrl,
        ]);

        // ── Guard: resolve user ────────────────────────────────────────────
        $user = User::find($this->userId);
        if (!$user) {
            Log::error('ProcessLinkedInProfileJob: User not found, aborting.', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        // ── Step 1: Fetch & parse LinkedIn profile via Gemini ──────────────
        $data = $vertexAi->scrapeAndParseLinkedIn($this->linkedinUrl);

        if ($data === null) {
            // The service already logged the specific reason; we skip gracefully
            // without failing the job so it does not retry on a known-blocked URL.
            Log::warning('ProcessLinkedInProfileJob: Gemini returned null — profile could not be parsed. Skipping.', [
                'user_id'      => $this->userId,
                'linkedin_url' => $this->linkedinUrl,
            ]);
            return;
        }

        // ── Step 2: Normalise arrays (must never be null in the database) ──
        $experiences = is_array($data['experiences'] ?? null) ? $data['experiences'] : [];
        $educations  = is_array($data['educations']  ?? null) ? $data['educations']  : [];

        // ── Step 3: Update tabel users ─────────────────────────────────────
        try {
            $updateData = [];

            if (!empty($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (!empty($data['photo_url'])) {
                $updateData['avatar_url'] = $data['photo_url'];
            }
            if (!empty($data['headline'])) {
                $updateData['position'] = $data['headline'];
            }
            if (!empty($data['bio_summary'])) {
                $updateData['bio'] = $data['bio_summary'];
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            Log::info('ProcessLinkedInProfileJob: users table updated successfully.', [
                'user_id' => $user->id,
                'fields'  => array_keys($updateData),
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessLinkedInProfileJob: Failed to update users table.', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            // Fail and let the queue retry once
            $this->fail($e);
            return;
        }

        // ── Step 4: Upsert tabel user_credentials ─────────────────────────
        try {
            UserCredential::updateOrCreate(
                ['user_id' => $user->id, 'provider' => 'linkedin'],
                [
                    'experience' => $experiences,
                    'education'  => $educations,
                    'raw_data'   => $data, // Full parsed payload for audit/debug
                ]
            );

            Log::info('ProcessLinkedInProfileJob: user_credentials upserted successfully.', [
                'user_id'           => $user->id,
                'experience_count'  => count($experiences),
                'education_count'   => count($educations),
            ]);
        } catch (\Exception $e) {
            // Non-fatal: profile is already updated; log and continue
            Log::error('ProcessLinkedInProfileJob: Failed to upsert user_credentials.', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        // ── Step 5: Invalidate Redis cache ─────────────────────────────────
        // Flush the cached match score so Discovery Feed recalculates fresh data
        // the next time this user's profile is requested.
        try {
            $cacheKey = "connectx:match_score:{$user->id}";
            Cache::forget($cacheKey);
            Log::info('ProcessLinkedInProfileJob: Match score cache invalidated.', [
                'user_id'   => $user->id,
                'cache_key' => $cacheKey,
            ]);
        } catch (\Exception $e) {
            // Non-fatal: cache miss is acceptable
            Log::warning('ProcessLinkedInProfileJob: Failed to invalidate cache.', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('ProcessLinkedInProfileJob: LinkedIn sync completed successfully.', [
            'user_id' => $user->id,
        ]);
    }
}
