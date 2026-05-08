<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Sends a localized push notification to a user based on a template
     */
    public function sendFromTemplate(User $user, string $templateName, array $placeholders = []): bool
    {
        if (!$user->fcm_token) {
            Log::warning("Cannot send push notification. User {$user->id} has no FCM token.");
            return false;
        }

        $template = NotificationTemplate::where('name', $templateName)->first();

        if (!$template) {
            Log::error("Notification template not found: {$templateName}");
            return false;
        }

        // Use app locale or default to english
        $lang = app()->getLocale() ?? 'en';
        
        // Handle fallback if specific language is missing in json
        $title = $template->title[$lang] ?? $template->title['en'] ?? 'Notification';
        $body = $template->body[$lang] ?? $template->body['en'] ?? '';

        // Default placeholders for name
        // Usually frontend sends name, or we get it from User model. Sometimes it's stored in profile fields.
        // Assuming $user->name or parsing it from email if null
        $userName = $user->name ?? $user->username ?? 'Builder';

        $defaultPlaceholders = [
            '[name]' => $userName,
            '[nama]' => $userName,
        ];

        $finalPlaceholders = array_merge($defaultPlaceholders, $placeholders);

        // Replace placeholders in title and body
        foreach ($finalPlaceholders as $key => $value) {
            $title = str_replace($key, $value, $title);
            $body = str_replace($key, $value, $body);
        }

        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withData([
                    'template' => $templateName,
                    'timestamp' => now()->toDateTimeString()
                ]);

            $this->messaging->send($message);
            Log::info("Push notification '{$templateName}' sent successfully to User {$user->id}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send push notification to User {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends a direct push notification to a user without a template
     */
    public function sendDirect(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user->fcm_token) {
            Log::warning("Cannot send direct push notification. User {$user->id} has no FCM token.");
            return false;
        }

        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withData(array_merge([
                    'timestamp' => now()->toDateTimeString()
                ], $data));

            $this->messaging->send($message);
            Log::info("Direct push notification '{$title}' sent successfully to User {$user->id}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send direct push notification to User {$user->id}: " . $e->getMessage());
            return false;
        }
    }
}
