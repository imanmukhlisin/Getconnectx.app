<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WhatsappLog;
use App\Models\WhatsappWebhookConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Webhook Controller
 *
 * Menangani webhook dari Meta WhatsApp Business API (Cloud API).
 * Endpoint: GET  /api/v1/webhook/whatsapp  → Verifikasi webhook Meta
 * Endpoint: POST /api/v1/webhook/whatsapp  → Menerima event (pesan, delivery, read)
 *
 * Konfigurasi di Meta App Dashboard:
 *   Callback URL : https://your-domain.com/api/v1/webhook/whatsapp
 *   Verify Token : (sama dengan WHATSAPP_META_VERIFY_TOKEN di .env)
 */
class WhatsappWebhookController extends Controller
{
    /**
     * GET — Verifikasi webhook dari Meta saat pertama kali didaftarkan.
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $config      = WhatsappWebhookConfig::where('provider', 'meta')->first();
        $verifyToken = $config?->verify_token ?? config('services.whatsapp.meta.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            // ✅ Verifikasi berhasil — update status di DB
            $config?->update([
                'webhook_verified' => true,
                'last_webhook_at'  => now(),
            ]);

            Log::info('WhatsApp webhook verified by Meta');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode'  => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * POST — Menerima event dari Meta (pesan masuk, delivery, read status).
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('WhatsApp webhook received', ['payload' => $payload]);

        // Verifikasi App Secret (signature check)
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp webhook: invalid signature');
            return response('Unauthorized', 401);
        }

        // Update last webhook timestamp
        WhatsappWebhookConfig::where('provider', 'meta')
            ->update(['last_webhook_at' => now()]);

        // ── Parse entry events ──────────────────────────────────────────────
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                match ($field) {
                    'messages'           => $this->handleMessages($value),
                    'message_deliveries' => $this->handleDeliveries($value),
                    'message_reads'      => $this->handleReads($value),
                    default              => Log::debug("WhatsApp webhook: unknown field [{$field}]"),
                };
            }
        }

        // Meta mengharapkan HTTP 200 OK segera
        return response()->json(['status' => 'ok']);
    }

    // ─── Event Handlers ───────────────────────────────────────────────────────

    /**
     * Pesan masuk dari user ke nomor bisnis.
     * (Berguna untuk chatbot atau dukungan pelanggan nanti)
     */
    private function handleMessages(array $value): void
    {
        $messages = $value['messages'] ?? [];

        foreach ($messages as $message) {
            $from      = $message['from'] ?? null;
            $messageId = $message['id'] ?? null;
            $text      = $message['text']['body'] ?? '[non-text message]';
            $type      = $message['type'] ?? 'text';

            Log::info('WhatsApp incoming message', [
                'from'       => $from,
                'message_id' => $messageId,
                'type'       => $type,
                'text'       => $text,
            ]);

            // TODO: Integrasikan dengan sistem chat ConnectX jika diperlukan
            // Untuk sekarang: hanya log saja
        }
    }

    /**
     * Update status delivery (pesan terkirim ke device penerima).
     */
    private function handleDeliveries(array $value): void
    {
        $statuses = $value['statuses'] ?? [];

        foreach ($statuses as $statusEvent) {
            $messageId = $statusEvent['id'] ?? null;
            $status    = $statusEvent['status'] ?? null; // delivered | read | sent | failed

            if (!$messageId) continue;

            $log = WhatsappLog::where('message_id', $messageId)->first();

            if ($log) {
                $updateData = ['status' => $status];

                if ($status === 'delivered') {
                    $updateData['delivered_at'] = now();
                } elseif ($status === 'read') {
                    $updateData['read_at'] = now();
                    $updateData['status']  = 'read';
                } elseif ($status === 'failed') {
                    $errorInfo = $statusEvent['errors'][0] ?? [];
                    $updateData['error_message'] = ($errorInfo['title'] ?? 'Unknown') . ': ' . ($errorInfo['message'] ?? '');
                }

                $log->update($updateData);

                Log::info('WhatsApp delivery status updated', [
                    'message_id' => $messageId,
                    'status'     => $status,
                ]);
            }
        }
    }

    /**
     * Update status read (pesan dibaca oleh penerima).
     */
    private function handleReads(array $value): void
    {
        $statuses = $value['statuses'] ?? [];

        foreach ($statuses as $statusEvent) {
            $messageId = $statusEvent['id'] ?? null;

            if (!$messageId) continue;

            WhatsappLog::where('message_id', $messageId)->update([
                'status'  => 'read',
                'read_at' => now(),
            ]);
        }
    }

    // ─── Security ─────────────────────────────────────────────────────────────

    /**
     * Verifikasi HMAC-SHA256 signature dari Meta.
     * Header: X-Hub-Signature-256: sha256=<hash>
     */
    private function verifySignature(Request $request): bool
    {
        $appSecret = config('services.whatsapp.meta.app_secret');

        // Jika app_secret belum dikonfigurasi, skip verifikasi (development mode)
        if (empty($appSecret)) {
            Log::warning('WhatsApp webhook: app_secret not configured, skipping signature verification');
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            $appSecret
        );

        return hash_equals($expectedSignature, $signature);
    }
}
