<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserCredential;
use App\Services\LinkedInApiService;
use App\Services\Discovery\VertexAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Background Job: Proses sinkronisasi profil LinkedIn user.
 *
 * Alur kerja:
 * 1. Fetch data profil dasar dari LinkedIn API (nama, foto, headline)
 * 2. Fetch riwayat pekerjaan (maks 3 terbaru) dan pendidikan
 * 3. Generate biografi profesional via Gemini AI
 * 4. Update tabel users (full_name, avatar_url, headline→position, bio, fcm_token, last_device_id)
 * 5. Upsert tabel user_credentials (experience, education — default [] jika kosong)
 * 6. Invalidate Redis cache match score agar dihitung ulang
 *
 * PENTING: Kolom experience dan education TIDAK BOLEH null.
 * Scoring Engine akan crash (NullPointerException) jika kolom ini null
 * saat menghitung Variabel G (riwayat pekerjaan) dan J (pendidikan).
 */
class ProcessLinkedInProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Batas waktu eksekusi job: 60 detik
     * (LinkedIn API + Gemini AI bisa cukup lambat)
     */
    public int $timeout = 60;

    /**
     * Jumlah percobaan ulang jika job gagal
     */
    public int $tries = 2;

    /**
     * Jeda (detik) sebelum retry pertama
     */
    public int $backoff = 10;

    // ─── Constructor ──────────────────────────────────────────────────

    public function __construct(
        protected User   $user,
        protected string $accessToken,
        protected string $fcmToken,
        protected string $deviceId,
    ) {}

    // ═══════════════════════════════════════════════════════════════════
    //  Handle: Titik Masuk Eksekusi Job
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Eksekusi job secara background.
     * Dependencies di-inject otomatis oleh Laravel Service Container.
     */
    public function handle(
        LinkedInApiService $linkedInApi,
        VertexAiService    $vertexAi,
    ): void {
        Log::info("ProcessLinkedInProfileJob: Mulai proses sync LinkedIn.", [
            'user_id' => $this->user->id,
        ]);

        // ── Step 1: Fetch profil dasar dari LinkedIn API ──────────────
        try {
            $profile = $linkedInApi->fetchProfile($this->accessToken);
        } catch (\Exception $e) {
            Log::error("ProcessLinkedInProfileJob: Gagal fetch profil LinkedIn.", [
                'user_id' => $this->user->id,
                'error'   => $e->getMessage(),
            ]);
            // Gagal di step 1 = job gagal total, Laravel akan retry
            $this->fail($e);
            return;
        }

        // ── Step 2: Fetch experience & education ──────────────────────
        // Kedua method ini sudah aman (tidak throw exception, default [])
        $experiences = $linkedInApi->fetchExperience($this->accessToken);
        $educations  = $linkedInApi->fetchEducation($this->accessToken);

        // ATURAN KETAT: Jangan pernah simpan null ke experience/education!
        // Scoring Engine (Variabel G & J) mengharapkan array, bukan null.
        $experiences = is_array($experiences) ? $experiences : [];
        $educations  = is_array($educations)  ? $educations  : [];

        Log::info("ProcessLinkedInProfileJob: Data LinkedIn berhasil di-fetch.", [
            'user_id'          => $this->user->id,
            'experience_count' => count($experiences),
            'education_count'  => count($educations),
        ]);

        // ── Step 3: Generate biografi profesional via Gemini AI ───────
        $headline = $profile['headline'] ?? ($this->user->position ?? '');
        $bio      = '';

        if (!empty($headline)) {
            $bio = $vertexAi->generateLinkedInBio($headline, $experiences);
        }

        // ── Step 4: Update tabel users ────────────────────────────────
        // Mapping LinkedIn → Kolom DB:
        //   full_name → users.name
        //   avatar_url → users.avatar_url
        //   headline → users.position (kolom yang sudah ada, efisien)
        //   bio_summary → users.bio (kolom yang sudah ada)
        //   fcm_token → users.fcm_token
        //   last_device_id → users.last_device_id
        try {
            $updateData = [
                'fcm_token'      => $this->fcmToken,
                'last_device_id' => $this->deviceId,
            ];

            // Hanya update jika ada data dari LinkedIn (hindari overwrite data user yang sudah bagus)
            if (!empty($profile['full_name'])) {
                $updateData['name'] = $profile['full_name'];
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

            $this->user->update($updateData);

            Log::info("ProcessLinkedInProfileJob: Tabel users berhasil diupdate.", [
                'user_id' => $this->user->id,
            ]);

        } catch (\Exception $e) {
            Log::error("ProcessLinkedInProfileJob: Gagal update tabel users.", [
                'user_id' => $this->user->id,
                'error'   => $e->getMessage(),
            ]);
            $this->fail($e);
            return;
        }

        // ── Step 5: Upsert tabel user_credentials ────────────────────
        // Gunakan updateOrCreate agar idempotent: jika sync dijalankan 2x,
        // tidak akan membuat baris duplikat.
        try {
            UserCredential::updateOrCreate(
                // Kondisi pencarian (unik per user + provider)
                [
                    'user_id'  => $this->user->id,
                    'provider' => 'linkedin',
                ],
                // Data yang akan di-insert atau di-update
                [
                    'experience' => $experiences, // [] jika kosong, BUKAN null
                    'education'  => $educations,  // [] jika kosong, BUKAN null
                    'raw_data'   => $profile['raw'] ?? null,
                ]
            );

            Log::info("ProcessLinkedInProfileJob: Tabel user_credentials berhasil di-upsert.", [
                'user_id' => $this->user->id,
            ]);

        } catch (\Exception $e) {
            Log::error("ProcessLinkedInProfileJob: Gagal upsert user_credentials.", [
                'user_id' => $this->user->id,
                'error'   => $e->getMessage(),
            ]);
            // Ini non-fatal: user sudah terupdate, hanya credential yang gagal
            // Jangan fail() agar tidak retry dari awal — cukup log saja
        }

        // ── Step 6: Invalidate Redis Cache Match Score ────────────────
        // Hapus cache match score user ini agar dihitung ulang saat
        // discovery cards berikutnya diakses (cache-aside pattern).
        try {
            $cachePattern = "connectx:discovery:ai_insight:{$this->user->id}:*";

            // Hapus semua cache insight untuk user ini
            // (Catatan: Cache::forget hanya untuk key eksak;
            //  untuk wildcard kita gunakan tag jika driver Redis mendukung)
            $cacheKey = "connectx:match_score:{$this->user->id}";
            Cache::forget($cacheKey);

            Log::info("ProcessLinkedInProfileJob: Cache match score di-invalidate.", [
                'user_id' => $this->user->id,
            ]);

        } catch (\Exception $e) {
            // Non-fatal: cache akan expire sendiri
            Log::warning("ProcessLinkedInProfileJob: Gagal invalidate cache.", [
                'user_id' => $this->user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        Log::info("ProcessLinkedInProfileJob: Selesai! LinkedIn sync sukses.", [
            'user_id' => $this->user->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Handler Kegagalan Job
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Dipanggil jika job gagal setelah semua percobaan habis.
     * Bisa digunakan untuk notifikasi ke Sentry, Slack, dll.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessLinkedInProfileJob: GAGAL setelah {$this->tries}x percobaan.", [
            'user_id' => $this->user->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
