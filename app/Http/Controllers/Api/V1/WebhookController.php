<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LinkedInScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle the webhook callback from Apify when the scraping run succeeds.
     */
    public function apifyLinkedIn(Request $request, LinkedInScraperService $scraperService)
    {
        // 1. Verifikasi tipe event (biasanya ACTOR.RUN.SUCCEEDED dari Apify Webhook)
        $eventType = $request->input('eventType');
        
        // Log full payload for debugging
        Log::info('Webhook apifyLinkedIn: Full payload received', $request->all());

        // Cek custom token/signature kalau ada (untuk security opsional)
        // Apify mengirim data body (json) dimana ID dataset ada di object "resource"
        $resource = $request->input('resource', []);
        $datasetId = $resource['defaultDatasetId'] ?? $request->input('eventData.defaultDatasetId'); // fallback
        $runId = $request->input('eventData.actorRunId');

        // Kami mengirim user_id lewat state atau record info kalau kita pakai webhook per run
        // Atau kita bisa gunakan endpoint pass-through parameter `passthrough` yang kita inject
        // Namun Apify webhook tak meneruskan payload passthrough di root, melainkan kita harus ambil dari Dataset.
        
        if (!$datasetId) {
            Log::warning('Webhook apifyLinkedIn: No datasetId received.');
            return response()->json(['message' => 'Ignored, missing datasetId'], 200);
        }

        try {
            // Ambil items dari dataset
            $apifyToken = config('services.apify.token');
            $endpoint = "https://api.apify.com/v2/datasets/{$datasetId}/items?token={$apifyToken}";
            
            $response = Http::get($endpoint);
            
            if ($response->successful()) {
                $items = $response->json();
                if (!empty($items) && is_array($items)) {
                    $scrapedData = $items[0];

                    // Temukan URL yang di-scrape
                    $urlScraped = $scrapedData['publicIdentifier'] ?? $scrapedData['url'] ?? null;

                    // Di script kita sebelumnya, publicIdentifier adalah username. 
                    // Apify fusion actor mereturn url di property "url"
                    
                    if (!$urlScraped) {
                        return response()->json(['message' => 'No URL found in dataset'], 200);
                    }

                    // Normalize the scraped URL to just the slug/publicIdentifier.
                    // Apify sometimes returns just the slug (e.g. "ananda-dimas-octavian-prasetyo")
                    // or a full URL. We extract only the path segment for comparison.
                    $scrapedSlug = rtrim(basename(parse_url(
                        str_starts_with($urlScraped, 'http') ? $urlScraped : "https://linkedin.com/in/{$urlScraped}"
                    , PHP_URL_PATH)), '/');

                    // Find user by matching the slug inside their stored linkedin_url.
                    // Using a normalized LIKE so that www vs non-www differences don't matter.
                    // We also order by created_at to prefer the OLDEST (real) account over duplicates.
                    $user = User::where('linkedin_url', 'LIKE', "%/{$scrapedSlug}%")
                                ->orderBy('created_at', 'asc')
                                ->first();

                    if ($user) {
                        $scraperService->syncToUserProfile($user, $scrapedData);
                        Log::info('Webhook apifyLinkedIn: Synced profile for User ID: ' . $user->id, [
                            'slug' => $scrapedSlug,
                        ]);

                        // Kirim Push Notification ke Frontend untuk trigger Auto-Refresh (GET /api/v1/me/profile)
                        if ($user->fcm_token) {
                            try {
                                app(\App\Services\PushNotificationService::class)->sendFromTemplate($user, 'linkedin_sync_complete');
                            } catch (\Exception $e) {
                                Log::error('Webhook apifyLinkedIn: Failed to send FCM trigger', ['error' => $e->getMessage()]);
                            }
                        }

                        return response()->json(['message' => 'Synced']);
                    } else {
                        Log::warning('Webhook apifyLinkedIn: User not found for LinkedIn slug: ' . $scrapedSlug);
                    }
                }
            } else {
                Log::error('Webhook apifyLinkedIn: Failed to fetch dataset', ['status' => $response->status()]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook apifyLinkedIn: Error processing webhook', [
                'message' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }

        return response()->json(['message' => 'Processed'], 200);
    }
}
