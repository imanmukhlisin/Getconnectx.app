<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLinkedInProfileJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller untuk menerima Webhook dari Apify setelah scraping selesai.
 */
class ApifyWebhookController extends Controller
{
    /**
     * Webhook Endpoint: POST /api/v1/webhooks/apify/linkedin
     * Dipanggil oleh server Apify (BUKAN frontend).
     */
    public function handle(Request $request)
    {
        // ── 1. Validasi Keamanan (Secret Token) ───────────────────────
        $expectedToken = config('services.apify.webhook_token', md5(config('services.apify.token', env('APIFY_TOKEN')) . 'webhook'));
        $receivedToken = $request->query('token');

        if ($receivedToken !== $expectedToken) {
            Log::warning('ApifyWebhookController: Invalid token provided.', [
                'received' => $receivedToken,
            ]);
            // Balas 401 Unauthorized agar Apify tahu
            return response()->json(['error' => 'Unauthorized webhook token'], 401);
        }

        // ── 2. Ambil user_id dari Query Parameter ─────────────────────
        $userId = $request->query('user_id');
        if (!$userId) {
            Log::warning('ApifyWebhookController: user_id is missing from webhook URL.');
            return response()->json(['error' => 'Missing user_id'], 400);
        }

        // ── 3. Ambil Dataset ID dari Event Apify ──────────────────────
        // Saat event "ACTOR.RUN.SUCCEEDED", payload JSON JSON body berisi detail run.
        $resource = $request->input('eventData') ?? $request->input('resource'); // Tergantung struktur terbaru Apify
        
        // Coba baca defaultDatasetId
        $datasetId = $resource['defaultDatasetId'] ?? null;

        if (!$datasetId) {
            Log::warning('ApifyWebhookController: defaultDatasetId is missing from payload.', [
                'payload' => $request->all(),
            ]);
            return response()->json(['error' => 'Missing dataset ID'], 400);
        }

        // ── 4. Dispatch Job untuk Proses Data ─────────────────────────
        Log::info("ApifyWebhookController: Received successful run for dataset {$datasetId}, user {$userId}.");

        // Kita delegate proses ambil dataset dan analisis AI ke Job background.
        ProcessLinkedInProfileJob::dispatch($userId, $datasetId);

        // ── 5. Balas 200 OK ke Apify ──────────────────────────────────
        return response()->json(['success' => true]);
    }
}
