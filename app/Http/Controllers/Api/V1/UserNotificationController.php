<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserNotificationController extends Controller
{
    /**
     * Get the current user's notifications.
     */
    #[OA\Get(
        path: '/api/v1/me/notifications',
        summary: 'Get current user notifications',
        security: [['sanctum' => []]],
        tags: ['Notifications'],
        responses: [
            new OA\Response(response: 200, description: 'Notifications fetched successfully'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min((int) $request->query('limit', 50), 100);

        $notifications = UserNotification::where('user_id', $user->id)
            ->with('actor:id,name,avatar_url')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $unreadCount = UserNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $mapped = $notifications->map(function ($notif) {
            $actor = $notif->actor;
            
            // Reconstruct target based on data payload
            $data = $notif->data ?? [];
            $targetKind = 'system';
            $targetId = null;

            if (isset($data['screen'])) {
                if ($data['screen'] === 'chat_room') {
                    $targetKind = 'conversation';
                    $targetId = $data['conversation_id'] ?? null;
                } elseif ($data['screen'] === 'match_success') {
                    $targetKind = 'match';
                    $targetId = $data['match_id'] ?? null;
                }
            }

            return [
                'id' => $notif->id,
                'type' => $notif->type,
                'title' => $notif->title,
                'body' => $notif->body,
                'createdAt' => $notif->created_at?->toIso8601String(),
                'readAt' => $notif->read_at?->toIso8601String(),
                'actor' => $actor ? [
                    'id' => $actor->id,
                    'name' => $actor->name,
                    'avatarUrl' => $actor->avatar_url,
                ] : null,
                'target' => [
                    'kind' => $targetKind,
                    'id' => $targetId,
                    'deepLink' => $data['screen'] ?? null,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Notifications fetched successfully',
            'data' => [
                'unreadCount' => $unreadCount,
                'notifications' => $mapped,
            ]
        ]);
    }

    /**
     * Mark a notification (or all) as read.
     */
    #[OA\Post(
        path: '/api/v1/me/notifications/read',
        summary: 'Mark notification(s) as read',
        security: [['sanctum' => []]],
        tags: ['Notifications'],
        responses: [
            new OA\Response(response: 200, description: 'Marked as read'),
        ]
    )]
    public function markRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'notification_id' => 'nullable|uuid',
            'mark_all' => 'nullable|boolean',
        ]);

        $query = UserNotification::where('user_id', $user->id)->whereNull('read_at');

        if ($request->input('mark_all')) {
            // Update all unread
            $query->update(['read_at' => now()]);
        } elseif ($request->filled('notification_id')) {
            // Update specific one
            $query->where('id', $request->input('notification_id'))
                  ->update(['read_at' => now()]);
        }

        $unreadCount = UserNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read',
            'data' => [
                'unreadCount' => $unreadCount
            ]
        ]);
    }
}
