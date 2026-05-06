<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LinkedInScraperService
{
    /**
     * Trigger Apify LinkedIn Scraper secara Asynchronous (run-async)
     * Apify akan mengembalikan webhook ke aplikasi kita setelah selesai.
     */
    public function triggerScrapeAsync(User $user, string $linkedinUrl): void
    {
        Log::info('LinkedInScraperService: Triggering Apify for', ['user_id' => $user->id, 'url' => $linkedinUrl]);

        // Try all common Apify token env var names
        $token = config('services.apify.token')      // APIFY_TOKEN via services.php
              ?? env('APIFY_API_TOKEN')              // Legacy name
              ?? env('APIFY_TOKEN');                 // Direct fallback

        // DEBUG: log token presence (REMOVE AFTER DEBUGGING)
        Log::info('LinkedInScraperService: Token check', [
            'has_token'   => !empty($token),
            'token_start' => $token ? substr($token, 0, 15) . '...' : 'NULL',
        ]);

        if (!$token) {
            Log::error('LinkedInScraperService: APIFY_API_TOKEN is missing.');
            return;
        }

        $actorId = 'harvestapi~linkedin-profile-scraper';

        // IMPORTANT: Use WEBHOOK_BASE_URL env var (set to Vercel production URL).
        // url() helper reads APP_URL which may be localhost in dev, making Apify unable to callback.
        $webhookBase = rtrim(config('app.webhook_base_url', config('app.url')), '/');
        $webhookUrl  = $webhookBase . '/api/v1/webhooks/apify/linkedin';

        Log::info('LinkedInScraperService: Using webhook URL', ['url' => $webhookUrl]);
        
        // Encode webhook configuration
        $webhooks = base64_encode(json_encode([
            [
                'eventTypes' => ['ACTOR.RUN.SUCCEEDED'],
                'requestUrl' => $webhookUrl
            ]
        ]));

        $url = "https://api.apify.com/v2/acts/{$actorId}/runs?token={$token}&webhooks={$webhooks}";

        try {
            $response = Http::post($url, [
                'urls' => [$linkedinUrl]
            ]);

            if (!$response->successful()) {
                Log::error('LinkedInScraperService: Failed to trigger Apify', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            } else {
                Log::info('LinkedInScraperService: Successfully triggered Apify', [
                    'run_id' => $response->json('data.id')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('LinkedInScraperService: Exception triggering Apify', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Memproses data JSON hasil scraping Apify dan menyimpannya ke User & UserCredential.
     */
    public function syncToUserProfile(User $user, array $scrapedData): void
    {
        Log::info('LinkedInScraperService: Syncing Apify data to user', ['user_id' => $user->id]);

        $updateData = [];

        // 1. Mapping Nama Lengkap
        $firstName = $scrapedData['firstName'] ?? '';
        $lastName = $scrapedData['lastName'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        if (!empty($fullName)) {
            $updateData['name'] = $fullName;
        }

        // 2. Mapping Foto Profil
        $photoUrl = $scrapedData['profilePicture']['url'] ?? $scrapedData['photo'] ?? null;
        if (!empty($photoUrl)) {
            $updateData['avatar_url'] = $photoUrl;
        }

        // 3. Mapping Headline/Position
        if (!empty($scrapedData['headline'])) {
            $updateData['position'] = $scrapedData['headline'];
        }

        // 4. Mapping About/Bio
        if (!empty($scrapedData['about'])) {
            $updateData['bio'] = $scrapedData['about'];
        }

        // 5. Mapping Location
        if (!empty($scrapedData['location']['parsed'])) {
            $updateData['city'] = $scrapedData['location']['parsed']['city'] ?? $updateData['city'] ?? null;
            $updateData['country'] = $scrapedData['location']['parsed']['country'] ?? $updateData['country'] ?? null;
        }

        // 6. Mapping Stats (Connections)
        if (isset($scrapedData['connectionsCount'])) {
            $updateData['connections_count'] = $scrapedData['connectionsCount'];
        }

        // Update tabel users
        if (!empty($updateData)) {
            $user->update($updateData);
            Log::info('LinkedInScraperService: user profile updated.', ['fields' => array_keys($updateData)]);
        }

        // 7. Mapping Experience & Education ke UserCredential
        $experiences = $scrapedData['experience'] ?? [];
        $educations = $scrapedData['education'] ?? [];

        // Format experience agar sesuai dengan format yang biasa dipakai frontend/backend jika perlu
        $formattedExperiences = array_map(function ($exp) {
            return [
                'title' => $exp['position'] ?? null,
                'company' => $exp['companyName'] ?? null,
                'period' => ($exp['startDate']['text'] ?? '') . ' - ' . ($exp['endDate']['text'] ?? 'Present'),
                'isCurrent' => !isset($exp['endDate']['year']),
                'location' => $exp['location'] ?? null,
                'description' => $exp['description'] ?? null
            ];
        }, is_array($experiences) ? $experiences : []);

        $formattedEducations = array_map(function ($edu) {
            return [
                'school' => $edu['schoolName'] ?? null,
                'degree' => $edu['degree'] ?? null,
                'field' => $edu['fieldOfStudy'] ?? null,
                'period' => ($edu['startDate']['text'] ?? '') . ' - ' . ($edu['endDate']['text'] ?? 'Present'),
                'description' => $edu['description'] ?? null
            ];
        }, is_array($educations) ? $educations : []);

        try {
            UserCredential::updateOrCreate(
                ['user_id' => $user->id, 'provider' => 'linkedin'],
                [
                    'experience' => $formattedExperiences,
                    'education'  => $formattedEducations,
                    'raw_data'   => $scrapedData, // Simpan raw dari Apify
                ]
            );
            Log::info('LinkedInScraperService: user_credentials updated.');
        } catch (\Exception $e) {
            Log::error('LinkedInScraperService: Failed to update user_credentials', ['error' => $e->getMessage()]);
        }

        // 8. Tambahkan skills dari Apify ke tag mapping (Optional)
        if (!empty($scrapedData['skills']) && is_array($scrapedData['skills'])) {
            $skillsToSync = collect($scrapedData['skills'])
                ->take(10)
                ->map(fn($s) => $s['name'])
                ->filter()
                ->toArray();
            
            if (!empty($skillsToSync)) {
                $tagIds = \App\Models\Tag::whereIn('name', $skillsToSync)->pluck('id')->toArray();
                if (!empty($tagIds)) {
                    // Sync without detaching existing tags
                    $user->tags()->syncWithoutDetaching($tagIds);
                }
            }
        }

        // 9. Invalidate Cache
        try {
            $cacheKey = "connectx:match_score:{$user->id}";
            Cache::forget($cacheKey);
        } catch (\Exception $e) {
            // Abaikan error cache
        }
    }
}
