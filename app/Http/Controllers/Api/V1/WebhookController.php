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
        
        // Cek custom token/signature kalau ada (untuk security opsional)
        // Apify mengirim data body (json): { "eventData": { "actorRunId": "...", "defaultDatasetId": "..." }, ... }
        $eventData = $request->input('eventData');
        $datasetId = $eventData['defaultDatasetId'] ?? null;
        $runId = $eventData['actorRunId'] ?? null;

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

                    // Cari user yang punya linkedin_url == $urlScraped
                    // Apify kadang memodifikasi trailing slash, pastikan pencarian flexible
                    $user = User::where('linkedin_url', 'LIKE', "%{$urlScraped}%")->first();

                    if ($user) {
                        $scraperService->syncToUserProfile($user, $scrapedData);
                        Log::info('Webhook apifyLinkedIn: Sycned profile for User ID: ' . $user->id);
                        return response()->json(['message' => 'Sycned']);
                    } else {
                        Log::warning('Webhook apifyLinkedIn: User not found for linkedin_url: ' . $urlScraped);
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
