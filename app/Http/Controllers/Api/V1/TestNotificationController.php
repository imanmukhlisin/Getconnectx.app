<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class TestNotificationController extends Controller
{
    protected $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    public function sendTest(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'template' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!$user->fcm_token) {
            return response()->json(['success' => false, 'message' => 'User has no FCM token'], 422);
        }

        $template = $request->template ?? 'account_created';
        
        $success = $this->pushService->sendFromTemplate($user, $template);

        if ($success) {
            return response()->json(['success' => true, 'message' => "Test push notification '{$template}' sent to {$user->email}"]);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to send notification'], 500);
        }
    }

    public function sendDirectTest(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!$user->fcm_token) {
            return response()->json(['success' => false, 'message' => 'User has no FCM token'], 422);
        }

        $success = $this->pushService->sendDirect($user, $request->title, $request->body);

        if ($success) {
            return response()->json(['success' => true, 'message' => "Direct push notification sent to {$user->email}"]);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to send notification'], 500);
        }
    }
}
