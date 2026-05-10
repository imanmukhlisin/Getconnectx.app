<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    private const ONLINE_THRESHOLD_MINUTES = 3;

    public function __construct(private PushNotificationService $pushNotificationService) {}

    // ─── 1. GET /conversations ───────────────────────────────────────────────
    /**
     * List all conversations for the authenticated user.
     * Returns the format specified in MESSAAGE-API-CONTARC.md.
     */
    public function index(Request $request)
    {
        $authUser = $request->user();
        $limit    = min((int) $request->query('limit', 20), 50);
        $page     = max((int) $request->query('page', 1), 1);

        $conversations = $authUser->conversations()
            ->with([
                'participants' => fn ($q) => $q->where('users.id', '!=', $authUser->id)->with('tokens'),
                'lastMessage.sender:id,name',
                'participantPivot' => fn ($q) => $q->where('user_id', $authUser->id),
            ])
            ->orderBy('last_message_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $mapped = $conversations->map(function ($conv) use ($authUser) {
            $other = $conv->participants->first(); // the non-auth participant

            if (!$other) {
                return null;
            }

            $isOnline = $other->tokens
                ->where('last_used_at', '>=', now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES))
                ->isNotEmpty();

            // Unread count: messages not sent by auth user and sent after auth user's last read
            $pivot         = $conv->participantPivot->first();
            $lastReadMsgId = $pivot?->last_read_message_id;

            $unreadCount = $conv->messages()
                ->where('sender_id', '!=', $authUser->id)
                ->when($lastReadMsgId, fn ($q) => $q->where('id', '!=', $lastReadMsgId)
                    ->whereRaw('created_at > (SELECT created_at FROM messages WHERE id = ?)', [$lastReadMsgId]))
                ->count();

            $lastMsg = $conv->lastMessage;

            return [
                'id'         => $conv->id,
                'match_id'   => null, // enriched via UserMatch if needed later
                'other_user' => [
                    'user_id'   => $other->id,
                    'name'      => $other->name,
                    'avatar_url' => $other->avatar_url,
                    'headline'  => $other->position,
                    'is_online' => $isOnline,
                ],
                'last_message'  => $lastMsg ? [
                    'id'      => $lastMsg->id,
                    'text'    => $lastMsg->type === 'text' ? $lastMsg->content : null,
                    'sent_by' => $lastMsg->sender_id,
                    'sent_at' => $lastMsg->created_at?->toIso8601String(),
                    'is_read' => $lastReadMsgId && $lastMsg->id === $lastReadMsgId,
                ] : null,
                'unread_count' => $unreadCount,
                'created_at'   => $conv->created_at?->toIso8601String(),
            ];
        })->filter()->values();

        return response()->json([
            'conversations' => $mapped,
            'total'         => $conversations->total(),
        ]);
    }

    // ─── 2. GET /conversations/{id}/messages ─────────────────────────────────
    /**
     * Cursor-based (infinite scroll upward) message history.
     * Use `before` (message id) + `limit` as per contract.
     */
    public function messages(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($conversation, $request->user());

        $limit  = min((int) $request->query('limit', 50), 100);
        $before = $request->query('before'); // cursor: message id

        $query = $conversation->messages()
            ->orderBy('created_at', 'desc');

        if ($before) {
            $cursorMsg = Message::find($before);
            if ($cursorMsg) {
                $query->where('created_at', '<', $cursorMsg->created_at);
            }
        }

        $messages = $query->limit($limit + 1)->get();

        $hasMore    = $messages->count() > $limit;
        $messages   = $messages->take($limit);
        $nextCursor = $hasMore ? $messages->last()?->id : null;

        $mapped = $messages->map(fn ($msg) => $this->formatMessage($msg));

        return response()->json([
            'messages'    => $mapped,
            'has_more'    => $hasMore,
            'next_cursor' => $nextCursor,
        ]);
    }

    // ─── 3. POST /conversations/{id}/messages ────────────────────────────────
    /**
     * Send a message (text or image via media_id).
     * Triggers FCM if target user is offline (Sanctum last_used_at).
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $authUser = $request->user();
        $this->authorizeParticipant($conversation, $authUser);

        $request->validate([
            'type'      => 'required|in:text,image',
            'text'      => 'required_if:type,text|string|max:5000',
            'media_id'  => 'required_if:type,image|uuid',
            'media_url' => 'nullable|url',
        ]);

        $type    = $request->input('type');
        $content = $type === 'text' ? $request->input('text') : null;

        // Resolve media: prefer media_url (from upload response), fallback to phantom message lookup
        $media = null;
        if ($type === 'image') {
            if ($request->filled('media_url')) {
                // Direct URL from upload response (recommended flow)
                $media = [
                    'url'           => $request->input('media_url'),
                    'thumbnail_url' => $request->input('media_url'),
                    'mime_type'     => $request->input('mime_type', 'image/jpeg'),
                    'size_bytes'    => $request->input('size_bytes', 0),
                ];
            } elseif ($request->filled('media_id')) {
                // Fallback: lookup phantom message record (legacy flow)
                $uploadedMsg = Message::find($request->input('media_id'));
                $media       = $uploadedMsg?->media;
            }
        }

        $message = DB::transaction(function () use ($authUser, $conversation, $type, $content, $media) {
            $msg = $conversation->messages()->create([
                'sender_id' => $authUser->id,
                'content'   => $content,
                'type'      => $type,
                'media'     => $media,
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $msg;
        });

        // ─── FCM: Always fire — Supabase Realtime not yet implemented on Flutter ─
        $target = $conversation->participants()
            ->where('users.id', '!=', $authUser->id)
            ->with('tokens')
            ->first();

        if ($target) {
            $isOnline = $target->tokens
                ->where('last_used_at', '>=', now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES))
                ->isNotEmpty();

            $senderName = $authUser->name ?? 'Someone';
            $this->pushNotificationService->sendFromTemplate(
                $target,
                'new_message',
                [
                    '[sender_name]' => $senderName,
                    '[sender]'      => $senderName,
                    '[message]'     => $type === 'text' ? ($content ?? '') : '📷 Image',
                ]
            );

            Log::debug('MessageController@sendMessage: FCM sent (realtime not yet active).', [
                'conversation_id' => $conversation->id,
                'target_user_id'  => $target->id,
                'target_online'   => $isOnline,
            ]);
        }


        return response()->json($this->formatMessage($message), 201);
    }

    // ─── 4. POST /conversations/{id}/read ────────────────────────────────────
    /**
     * Mark the last read message in a conversation.
     * Updates `last_read_message_id` in conversation_participants.
     */
    public function markRead(Request $request, Conversation $conversation)
    {
        $authUser = $request->user();
        $this->authorizeParticipant($conversation, $authUser);

        $request->validate([
            'last_read_message_id' => 'required|uuid|exists:messages,id',
        ]);

        $msgId = $request->input('last_read_message_id');

        // Update pivot
        DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $authUser->id)
            ->update(['last_read_message_id' => $msgId]);

        // Also stamp read_at on all messages up to this one
        $readUntil = Message::find($msgId)?->created_at;
        if ($readUntil) {
            $conversation->messages()
                ->where('sender_id', '!=', $authUser->id)
                ->where('created_at', '<=', $readUntil)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return response()->json(['status' => 'ok']);
    }

    // ─── 5. GET /conversations/{id}/media ────────────────────────────────────
    /**
     * All images/media shared in a conversation.
     */
    public function media(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($conversation, $request->user());

        $limit = min((int) $request->query('limit', 30), 100);

        $mediaMessages = $conversation->messages()
            ->where('type', 'image')
            ->whereNotNull('media')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($msg) => [
                'message_id' => $msg->id,
                'sender_id'  => $msg->sender_id,
                'media'      => $msg->media,
                'sent_at'    => $msg->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'media' => $mediaMessages,
        ]);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function authorizeParticipant(Conversation $conversation, User $user): void
    {
        if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
            abort(403, 'You are not a participant of this conversation.');
        }
    }

    private function formatMessage(Message $msg): array
    {
        return [
            'id'              => $msg->id,
            'conversation_id' => $msg->conversation_id,
            'sender_id'       => $msg->sender_id,
            'type'            => $msg->type,
            'text'            => $msg->type === 'text' ? $msg->content : null,
            'media'           => $msg->type === 'image' ? $msg->media : null,
            'sent_at'         => $msg->created_at?->toIso8601String(),
            'read_at'         => $msg->read_at?->toIso8601String(),
        ];
    }
}
