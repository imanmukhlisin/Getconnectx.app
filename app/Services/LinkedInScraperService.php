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
        Log::info('LinkedInScraperService: Triggering Apify (sync) for', ['user_id' => $user->id, 'url' => $linkedinUrl]);

        // Try all common Apify token env var names
        $token = config('services.apify.token')
              ?? env('APIFY_API_TOKEN')
              ?? env('APIFY_TOKEN');

        if (!$token) {
            Log::error('LinkedInScraperService: Apify token is missing. Set APIFY_TOKEN in env.');
            return;
        }

        $actorId = 'harvestapi~linkedin-profile-scraper';

        // ── Synchronous Run ──────────────────────────────────────────────
        // The actor finishes in ~10s. Using run-sync-get-dataset-items so we
        // get the results directly in the HTTP response — no webhook needed.
        // Vercel Pro functions support up to 60s timeout; 45s is safe.
        $url = "https://api.apify.com/v2/acts/{$actorId}/run-sync-get-dataset-items?token={$token}";

        try {
            Log::info('LinkedInScraperService: Calling Apify run-sync...', ['url' => $linkedinUrl]);

            $response = Http::timeout(45)->post($url, [
                'urls' => [$linkedinUrl]
            ]);

            if (!$response->successful()) {
                Log::error('LinkedInScraperService: Apify sync failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return;
            }

            $items = $response->json();

            if (empty($items) || !is_array($items) || empty($items[0])) {
                Log::warning('LinkedInScraperService: Apify returned no items.', ['user_id' => $user->id]);
                return;
            }

            Log::info('LinkedInScraperService: Apify sync succeeded, processing data.', [
                'user_id'    => $user->id,
                'item_count' => count($items),
            ]);

            // Process & save the scraping result directly
            $this->syncToUserProfile($user, $items[0]);

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
