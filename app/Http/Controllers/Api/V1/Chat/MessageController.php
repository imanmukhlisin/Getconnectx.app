<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * List all conversations for the authenticated user.
     */
    public function index(Request $request)
    {
        $conversations = $request->user()->conversations()
            ->with(['participants' => function ($query) use ($request) {
                $query->where('users.id', '!=', $request->user()->id);
            }, 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $conversations,
        ]);
    }

    /**
     * Start or find a conversation with another user.
     */
    public function storeConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
        ]);

        $recipientId = $request->user_id;
        $authUserId = $request->user()->id;

        // Prevent self-chat
        if ($recipientId === $authUserId) {
            return response()->json(['message' => 'You cannot chat with yourself.'], 400);
        }

        // Find existing 1-on-1 conversation
        $conversation = Conversation::whereHas('participants', function ($q) use ($authUserId) {
            $q->where('user_id', $authUserId);
        })->whereHas('participants', function ($q) use ($recipientId) {
            $q->where('user_id', $recipientId);
        })->has('participants', '=', 2)->first();

        if (!$conversation) {
            $conversation = DB::transaction(function () use ($authUserId, $recipientId) {
                $conv = Conversation::create(['last_message_at' => now()]);
                $conv->participants()->attach([$authUserId, $recipientId]);
                return $conv;
            });
        }

        return response()->json([
            'status' => 'success',
            'data'   => $conversation->load('participants'),
        ]);
    }

    /**
     * Get messages for a specific conversation.
     */
    public function messages(Request $request, Conversation $conversation)
    {
        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,name,avatar_url')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'status' => 'success',
            'data'   => $messages,
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'type'    => 'string|in:text,image,file',
        ]);

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = DB::transaction(function () use ($request, $conversation) {
            $msg = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'content'   => $request->input('content'),
                'type'      => $request->input('type', 'text'),
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $msg;
        });

        return response()->json([
            'status' => 'success',
            'data'   => $message->load('sender:id,name,avatar_url'),
        ], 201);
    }

    /**
     * Get shared media (images/files) for a specific conversation.
     */
    public function media(Request $request, Conversation $conversation)
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mediaList = $conversation->messages()
            ->whereIn('type', ['image', 'file'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'status' => 'success',
            'data'   => $mediaList,
        ]);
    }

    /**
     * Send media message (image upload).
     */
    public function sendMedia(Request $request, Conversation $conversation)
    {
        $request->validate([
            'file' => 'required|image|max:10240', // Max 10MB
        ]);

        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $path = $request->file('file')->store('chat-media', 'public');
        $url = asset('storage/' . $path);

        $message = DB::transaction(function () use ($request, $conversation, $url) {
            $msg = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'content'   => $url,
                'type'      => 'image',
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $msg;
        });

        return response()->json([
            'status' => 'success',
            'data'   => $message->load('sender:id,name,avatar_url'),
        ], 201);
    }

    /**
     * Signal that the user is typing (for real-time heartbeats).
     */
    public function signalTyping(Request $request, Conversation $conversation)
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Technically this just verifies the user has access.
        // The real-time broadcast can be triggered here if using Laravel Reverb/Pusher,
        // or the client can just broadcast via Supabase directly.
        // We'll return a success to confirm the "Heartbeat" intent was received.
        return response()->json([
            'status'  => 'success',
            'message' => 'Typing signal received.',
        ]);
    }
}
