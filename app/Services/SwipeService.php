<?php

namespace App\Services;

use App\Jobs\GenerateMatchAnalysisJob;
use App\Models\Conversation;
use App\Models\Like;
use App\Models\UserMatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

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
    public function connect(string $fromUserId, string $targetUserId, string $viewerContext = 'talent'): array
    {
        $this->guardSelfSwipe($fromUserId, $targetUserId);

        // Run DB operations in transaction (excluding job dispatch — see below)
        $result = DB::transaction(function () use ($fromUserId, $targetUserId, $viewerContext) {

            // 1. Prevent duplicate — if already swiped, return existing result
            $existing = Like::where('from_user_id', $fromUserId)
                            ->where('to_user_id', $targetUserId)
                            ->first();

            if ($existing) {
                return $this->buildConnectResult($existing->is_mutual, $fromUserId, $targetUserId, $viewerContext);
            }

            // 2. Check if target already connected to us (mutual)
            $reverseLike = Like::where('from_user_id', $targetUserId)
                               ->where('to_user_id', $fromUserId)
                               ->where('type', Like::TYPE_CONNECT)
                               ->first();

            $isMutual = $reverseLike !== null;

            // 3. Persist the new like (with context)
            Like::create([
                'from_user_id'   => $fromUserId,
                'to_user_id'     => $targetUserId,
                'type'           => Like::TYPE_CONNECT,
                'is_mutual'      => $isMutual,
                'viewer_context' => $viewerContext,
            ]);

            $match = null;

            if ($isMutual) {
                // 4a. Mark the reverse like as mutual too
                $reverseLike->update(['is_mutual' => true]);

                // 4b. Create match + conversation atomically (pass context)
                $match = $this->createMatchAndConversation($fromUserId, $targetUserId, $viewerContext);

                // 4c. Invalidate feed cache for both users
                app(FeedService::class)->invalidateUserFeedCache($fromUserId);
                app(FeedService::class)->invalidateUserFeedCache($targetUserId);

                // 4d. Send Push Notifications for Match
                $userA = \App\Models\User::find($fromUserId);
                $userB = \App\Models\User::find($targetUserId);
                
                if ($userA && $userB) {
                    \App\Jobs\SendPushNotificationJob::dispatch($userB, 'new_match', [
                        '[name]' => $userA->name ?? 'Someone',
                        '[nama]' => $userA->name ?? 'Seseorang'
                    ], [
                        'screen'          => 'match_success',
                        'match_id'        => $match->id,
                        'conversation_id' => $match->conversation_id,
                    ]);
                    \App\Jobs\SendPushNotificationJob::dispatch($userA, 'new_match', [
                        '[name]' => $userB->name ?? 'Someone',
                        '[nama]' => $userB->name ?? 'Seseorang'
                    ], [
                        'screen'          => 'match_success',
                        'match_id'        => $match->id,
                        'conversation_id' => $match->conversation_id,
                    ]);
                }

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

        // 4d. Dispatch analysis job OUTSIDE the transaction so a sync-queue
        //     failure (QUEUE_CONNECTION=sync on Vercel) cannot roll back the match.
        if (!empty($result['matchId'])) {
            try {
                GenerateMatchAnalysisJob::dispatch($result['matchId'], $fromUserId, $targetUserId);
            } catch (\Throwable $e) {
                Log::error('SwipeService: GenerateMatchAnalysisJob failed (non-fatal)', [
                    'match_id' => $result['matchId'],
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $result;
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
    public function skip(string $fromUserId, string $targetUserId, string $viewerContext = 'talent'): array
    {
        $this->guardSelfSwipe($fromUserId, $targetUserId);

        // Idempotent — if already skipped/connected, do nothing
        Like::firstOrCreate(
            ['from_user_id' => $fromUserId, 'to_user_id' => $targetUserId],
            ['type' => Like::TYPE_SKIP, 'is_mutual' => false, 'viewer_context' => $viewerContext]
        );

        // Remove target from feed cache
        app(FeedService::class)->invalidateUserFeedCache($fromUserId);

        return ['success' => true];
    }

    /**
     * Handle rewind action (undo last swipe).
     * Only rewinds the most recent swipe that was NOT a mutual match.
     *
     * @param string $userId
     * @return array|null The rewound like array or null if history is empty or unrewindable
     */
    public function rewind(string $userId): ?array
    {
        // Find the most recent like
        $lastSwipe = Like::where('from_user_id', $userId)
                         ->latest('created_at')
                         ->first();

        if (!$lastSwipe) {
            return null; // EMPTY_HISTORY
        }

        // Check if it's already a mutual match
        if ($lastSwipe->is_mutual) {
            // Unrewindable because it resulted in a mutual match
            throw new Exception("ALREADY_MATCHED");
        }

        $targetId = $lastSwipe->to_user_id;
        $action = $lastSwipe->type === Like::TYPE_SKIP ? 'pass' : 'like';

        // Delete the like record
        $lastSwipe->delete();

        // Target can re-appear in feed cache, invalidate
        app(FeedService::class)->invalidateUserFeedCache($userId);

        return [
            'targetUserId' => $targetId,
            'action' => $action
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Private Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create the UserMatch record and its associated Conversation.
     */
    private function createMatchAndConversation(string $userA, string $userB, string $viewerContext = 'talent'): UserMatch
    {
        $conversation = Conversation::create(['last_message_at' => now()]);
        $conversation->participants()->attach([$userA, $userB]);

        return UserMatch::create([
            'user_id'         => $userA,
            'matched_user_id' => $userB,
            'status'          => 'active',
            'matched_at'      => now(),
            'conversation_id' => $conversation->id,
            'viewer_context'  => $viewerContext, // Store the context that triggered this match
        ]);
    }

    /**
     * Build the connect response when a duplicate swipe is detected.
     * We look up whether a match already exists for them.
     */
    private function buildConnectResult(bool $isMutual, string $fromUserId, string $targetUserId, string $viewerContext = 'talent'): array
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
