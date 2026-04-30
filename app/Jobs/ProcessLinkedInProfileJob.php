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
 * 1. Dipanggil oleh ApifyWebhookController dengan userID dan datasetID
 * 2. Fetch data profiling dari Apify menggunakan dataset ID
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
    protected string $datasetId;

    public function __construct(string $userId, string $datasetId)
    {
        $this->userId = $userId;
        $this->datasetId = $datasetId;
    }

    public function handle(VertexAiService $vertexAi): void
    {
        Log::info("ProcessLinkedInProfileJob: Mulai fetch Apify dataset.", [
            'user_id'    => $this->userId,
            'dataset_id' => $this->datasetId,
        ]);

        $user = User::find($this->userId);
        if (!$user) {
            Log::error("ProcessLinkedInProfileJob: User tidak ditemukan.", ['user_id' => $this->userId]);
            return;
        }

        // ── Step 1: Fetch Apify Dataset atau Gunakan Mock Data ─────────────────────────────────
        if ($this->datasetId === 'MOCK_DATASET') {
            Log::info("ProcessLinkedInProfileJob: Menggunakan MOCK DATA untuk bypass Apify limits.");
            $datasetData = [
                'firstName' => 'Mukhlis',
                'lastName'  => 'ConnectX',
                'headline'  => 'Senior Fullstack Engineer & Tech Lead',
                'profilePictureUrl' => 'https://ui-avatars.com/api/?name=Mukhlis+ConnectX&background=random&size=256',
                'experience' => [
                    [
                        'title' => 'Senior Backend Engineer',
                        'companyName' => 'Tech Startup Inc.',
                        'duration' => ['startDate' => '2021', 'endDate' => null]
                    ],
                    [
                        'title' => 'Software Engineer',
                        'companyName' => 'Global Corp',
                        'duration' => ['startDate' => '2018', 'endDate' => '2021']
                    ]
                ],
                'education' => [
                    [
                        'degreeName' => 'Bachelor of Computer Science',
                        'schoolName' => 'University of Technology',
                        'duration' => ['startDate' => '2014', 'endDate' => '2018']
                    ]
                ]
            ];
        } else {
            $apifyToken = config('services.apify.token', env('APIFY_TOKEN'));
            if (!$apifyToken) {
                Log::error("ProcessLinkedInProfileJob: APIFY_TOKEN is missing.");
                return;
            }

            $datasetUrl = "https://api.apify.com/v2/datasets/{$this->datasetId}/items?token={$apifyToken}";
            $response = Http::timeout(15)->get($datasetUrl);

            if (!$response->successful() || empty($response->json())) {
                Log::error("ProcessLinkedInProfileJob: Gagal baca atau kosong Apify Dataset.", [
                    'status' => $response->status(),
                    'body'   => $response->body()
                ]);
                $this->fail(new \Exception("Cannot fetch dataset from Apify."));
                return;
            }

            // Mengambil array item (data orang pertama di index 0)
            $datasetData = $response->json()[0] ?? null;
            
            if (!$datasetData) {
                Log::error("ProcessLinkedInProfileJob: Array kosong dicoba dari dataset Apify.");
                return;
            }
        }

        // ── Step 2: Ekstrak Experience & Education ──────────────────────
        // Normalisasi format experience
        $rawExp = $datasetData['experience'] ?? [];
        $experiences = collect($rawExp)->take(3)->map(function ($item) {
            $start = $item['duration']['startDate'] ?? null;
            $end = $item['duration']['endDate'] ?? 'Present';
            $period = trim("{$start} - {$end}", " -");

            return [
                'title'     => $item['title'] ?? null,
                'company'   => $item['companyName'] ?? null,
                'period'    => $period,
                'isCurrent' => $item['duration']['endDate'] === null,
            ];
        })->toArray();

        // Normalisasi format education
        $rawEdu = $datasetData['education'] ?? [];
        $educations = collect($rawEdu)->map(function ($item) {
            $start = $item['duration']['startDate'] ?? null;
            $end = $item['duration']['endDate'] ?? 'Present';
            $period = trim("{$start} - {$end}", " -");

            return [
                'degree' => $item['degreeName'] ?? null,
                'school' => $item['schoolName'] ?? null,
                'period' => $period,
            ];
        })->toArray();

        // ATURAN KETAT: jangan null.
        $experiences = is_array($experiences) ? $experiences : [];
        $educations  = is_array($educations)  ? $educations  : [];

        // ── Step 3: Generate biografi profesional via Gemini AI ───────
        $headline = $datasetData['headline'] ?? ($user->position ?? '');
        $bio      = '';

        if (!empty($headline)) {
            $bio = $vertexAi->generateLinkedInBio($headline, $experiences);
        }

        // ── Step 4: Update tabel users ────────────────────────────────
        try {
            $updateData = [];
            
            $firstName = $datasetData['firstName'] ?? '';
            $lastName  = $datasetData['lastName'] ?? '';
            $fullName  = trim("{$firstName} {$lastName}");

            if (!empty($fullName)) {
                $updateData['name'] = $fullName;
            }
            if (!empty($datasetData['profilePictureUrl'])) {
                $updateData['avatar_url'] = $datasetData['profilePictureUrl'];
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
                    'raw_data'   => $datasetData,
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

        Log::info("ProcessLinkedInProfileJob: Selesai! LinkedIn sync via Webhook sukses.");
    }
}
