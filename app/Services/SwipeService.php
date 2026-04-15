<?php

namespace App\Services;

use App\Jobs\GenerateMatchAnalysisJob;
use App\Models\Conversation;
use App\Models\Like;
use App\Models\UserMatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SwipeService
{
    // ─── Cache TTL & key prefix (mirror FeedService) ──────────────────────────
    private const CACHE_PREFIX = 'connectx:feed:';

    // ─────────────────────────────────────────────────────────────────────────
    //  Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Handle swipe-right (connect).
     *
     * Flow:
     *  1. Guard: cannot swipe yourself.
     *  2. Guard: prevent duplicate swipe (return existing record).
     *  3. DB transaction:
     *     a. Insert like (type=connect).
     *     b. Check for mutual — if yes, create match + conversation + dispatch analysis job.
     *  4. Invalidate feed cache for both users (if match created).
     *
     * @param  string  $fromUserId  Authenticated user
     * @param  string  $targetUserId
     * @return array{isMatch: bool, matchId: string|null, conversationId: string|null}
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function connect(string $fromUserId, string $targetUserId): array
    {
        $this->guardSelfSwipe($fromUserId, $targetUserId);

        return DB::transaction(function () use ($fromUserId, $targetUserId) {

            // 1. Prevent duplicate — if already swiped, return existing result
            $existing = Like::where('from_user_id', $fromUserId)
                            ->where('to_user_id', $targetUserId)
                            ->first();

            if ($existing) {
                return $this->buildConnectResult($existing->is_mutual, $fromUserId, $targetUserId);
            }

            // 2. Check if target already connected to us (mutual)
            $reverseLike = Like::where('from_user_id', $targetUserId)
                               ->where('to_user_id', $fromUserId)
                               ->where('type', Like::TYPE_CONNECT)
                               ->first();

            $isMutual = $reverseLike !== null;

            // 3. Persist the new like
            $like = Like::create([
                'from_user_id' => $fromUserId,
                'to_user_id'   => $targetUserId,
                'type'         => Like::TYPE_CONNECT,
                'is_mutual'    => $isMutual,
            ]);

            $match = null;

            if ($isMutual) {
                // 4a. Mark the reverse like as mutual too
                $reverseLike->update(['is_mutual' => true]);

                // 4b. Create match + conversation atomically
                $match = $this->createMatchAndConversation($fromUserId, $targetUserId);

                // 4c. Invalidate feed cache for both users
                app(FeedService::class)->invalidateUserFeedCache($fromUserId);
                app(FeedService::class)->invalidateUserFeedCache($targetUserId);

                // 4d. Dispatch async match analysis
                GenerateMatchAnalysisJob::dispatch($match->id, $fromUserId, $targetUserId);

            } else {
                // Still invalidate own feed (target disappears from feed)
                app(FeedService::class)->invalidateUserFeedCache($fromUserId);
            }

            return [
                'isMatch'        => $isMutual,
                'matchId'        => $match?->id,
                'conversationId' => $match?->conversation_id,
            ];
        });
    }

    /**
     * Handle swipe-left (skip).
     *
     * @param  string  $fromUserId
     * @param  string  $targetUserId
     * @return array{success: bool}
     *
     * @throws \InvalidArgumentException
     */
    public function skip(string $fromUserId, string $targetUserId): array
    {
        $this->guardSelfSwipe($fromUserId, $targetUserId);

        // Idempotent — if already skipped/connected, do nothing
        Like::firstOrCreate(
            ['from_user_id' => $fromUserId, 'to_user_id' => $targetUserId],
            ['type' => Like::TYPE_SKIP, 'is_mutual' => false]
        );

        // Remove target from feed cache
        app(FeedService::class)->invalidateUserFeedCache($fromUserId);

        return ['success' => true];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Private Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create the UserMatch record and its associated Conversation.
     */
    private function createMatchAndConversation(string $userA, string $userB): UserMatch
    {
        $conversation = Conversation::create(['last_message_at' => now()]);
        $conversation->participants()->attach([$userA, $userB]);

        return UserMatch::create([
            'user_id'         => $userA,
            'matched_user_id' => $userB,
            'status'          => 'active',
            'matched_at'      => now(),
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Build the connect response when a duplicate swipe is detected.
     * We look up whether a match already exists for them.
     */
    private function buildConnectResult(bool $isMutual, string $fromUserId, string $targetUserId): array
    {
        $match = null;

        if ($isMutual) {
            $match = UserMatch::where(function ($q) use ($fromUserId, $targetUserId) {
                $q->where('user_id', $fromUserId)->where('matched_user_id', $targetUserId);
            })->orWhere(function ($q) use ($fromUserId, $targetUserId) {
                $q->where('user_id', $targetUserId)->where('matched_user_id', $fromUserId);
            })->first();
        }

        return [
            'isMatch'        => $isMutual,
            'matchId'        => $match?->id,
            'conversationId' => $match?->conversation_id,
        ];
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function guardSelfSwipe(string $fromUserId, string $targetUserId): void
    {
        if ($fromUserId === $targetUserId) {
            throw new \InvalidArgumentException('You cannot swipe yourself.');
        }
    }
}
