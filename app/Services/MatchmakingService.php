<?php

namespace App\Services;

use App\Jobs\GenerateMatchAnalysisJob;
use App\Models\Conversation;
use App\Models\Like;
use App\Models\UserMatch;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    /**
     * Handle user swipe "Like".
     *
     * @param string $fromUserId
     * @param string $toUserId
     * @return array
     * @throws \Exception
     */
    public function likeUser(string $fromUserId, string $toUserId): array
    {
        if ($fromUserId === $toUserId) {
            throw new \Exception("You cannot like yourself.");
        }

        return DB::transaction(function () use ($fromUserId, $toUserId) {
            // Cek apakah 'User To' sudah me-like 'User From' (Reverse Like)
            $reverseLike = Like::where('from_user_id', $toUserId)
                               ->where('to_user_id', $fromUserId)
                               ->first();

            $isMutual = $reverseLike !== null;

            // Simpan like dari From ke To
            $like = Like::firstOrCreate(
                ['from_user_id' => $fromUserId, 'to_user_id' => $toUserId],
                ['is_mutual' => $isMutual]
            );

            $match = null;

            if ($isMutual) {
                // Update reverse like to mutual
                $reverseLike->update(['is_mutual' => true]);
                $like->update(['is_mutual' => true]);

                // Create mutual match
                $match = $this->createMatchAndConversation($fromUserId, $toUserId);
                
                // Dispatch Queue Job untuk menganalisis kecocokan secara Async
                GenerateMatchAnalysisJob::dispatch($match->id, $fromUserId, $toUserId);
            }

            return [
                'like' => $like,
                'is_mutual' => $isMutual,
                'match' => $match
            ];
        });
    }

    /**
     * Create the Match and integrate it with the Chat System (Conversation)
     */
    private function createMatchAndConversation(string $userA, string $userB)
    {
        // 1. Buat Ruangan Chat Baru
        $conversation = Conversation::create(['last_message_at' => now()]);
        
        // 2. Masukkan kedua ke partisipan
        $conversation->participants()->attach([$userA, $userB]);

        // 3. Simpan tabel Match
        $match = UserMatch::create([
            'user_id' => $userA,
            'matched_user_id' => $userB,
            'status' => 'active',
            'conversation_id' => $conversation->id,
        ]);

        return $match;
    }
}
