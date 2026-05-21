<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RevenueCatWebhookController extends Controller
{
    /**
     * Handle RevenueCat Webhook Events
     */
    public function handle(Request $request)
    {
        // 1. Security Check: Verify Authorization Header
        $authToken = config('services.revenuecat.webhook_token');
        if ($authToken) {
            $header = $request->header('Authorization');
            if ($header !== $authToken) {
                Log::warning('RevenueCat Webhook: Unauthorized attempt', ['header' => $header]);
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $event = $request->input('event');

        if (!$event) {
            return response()->json(['message' => 'No event payload'], 400);
        }

        $eventType = $event['type'] ?? null;
        $appUserId = $event['app_user_id'] ?? null;
        $entitlementIds = $event['entitlement_ids'] ?? [];

        Log::info('RevenueCat Webhook Received', [
            'type' => $eventType,
            'app_user_id' => $appUserId,
            'entitlements' => $entitlementIds,
        ]);

        if (!$appUserId) {
            return response()->json(['message' => 'Ignored, missing app_user_id'], 200);
        }

        // Clean RevenueCat prefix if present
        $cleanUserId = str_replace('connectx_', '', $appUserId);

        if (!\Illuminate\Support\Str::isUuid($cleanUserId)) {
            Log::warning('RevenueCat Webhook: Invalid UUID format', ['app_user_id' => $appUserId, 'clean' => $cleanUserId]);
            return response()->json(['message' => 'Invalid app_user_id format'], 400);
        }

        $user = User::find($cleanUserId);

        if (!$user) {
            Log::warning('RevenueCat Webhook: User not found', ['app_user_id' => $appUserId]);
            return response()->json(['message' => 'User not found'], 404);
        }

        // We specifically check for the "connectx_pro" entitlement
        $entitlementIds = is_array($entitlementIds) ? $entitlementIds : [];
        $hasProEntitlement = in_array('connectx_pro', $entitlementIds);

        switch ($eventType) {
            case 'INITIAL_PURCHASE':
            case 'RENEWAL':
            case 'UNCANCELLATION':
            case 'NON_RENEWING_PURCHASE':
                if ($hasProEntitlement) {
                    $user->update(['is_pro' => true]);
                    Log::info('RevenueCat Webhook: User upgraded to PRO', ['user_id' => $user->id]);
                    
                    if ($eventType === 'INITIAL_PURCHASE') {
                        \App\Jobs\SendPushNotificationJob::dispatch($user, 'premium_activated', [], [
                            'screen' => 'pro_status'
                        ]);
                    }
                }
                break;

            case 'EXPIRATION':
                if ($hasProEntitlement) {
                    // If the specific entitlement expired
                    $user->update(['is_pro' => false]);
                    Log::info('RevenueCat Webhook: User PRO expired', ['user_id' => $user->id]);
                }
                break;
                
            case 'CANCELLATION':
                // Cancellation doesn't mean immediate expiration (they might still have time left).
                // RevenueCat will send an EXPIRATION event when the time is actually up.
                Log::info('RevenueCat Webhook: User cancelled auto-renewal', ['user_id' => $user->id]);
                break;

            default:
                Log::info('RevenueCat Webhook: Ignored event type', ['type' => $eventType]);
                break;
        }

        return response()->json(['message' => 'Processed'], 200);
    }
}
