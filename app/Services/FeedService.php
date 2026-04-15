<?php

namespace App\Services;

use App\Models\Like;
use App\Models\User;
use App\Models\UserMatch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class FeedService
{
    // ─── Config ───────────────────────────────────────────────────────────────
    private const PER_PAGE    = 10;
    private const CACHE_TTL   = 300;          // 5 minutes (seconds)
    public const CACHE_PREFIX = 'connectx:feed:';

    // ─────────────────────────────────────────────────────────────────────────
    //  Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return a paginated, scored discovery feed for the given user.
     *
     * Filters supported:
     *   - industry   : string  — match user's startup_stage / industry tag
     *   - role       : string  — filter by role_category
     *   - availability: string — filter by commitment_level
     *   - stage      : string  — filter by startup_stage
     *   - radius     : float   — km radius (requires user to have lat/lng)
     *
     * Scoring (computed in SQL for efficiency):
     *   score = (interestOverlap * 0.5) + (roleComplement * 0.3) + (locationScore * 0.2)
     *
     * @param  User   $authUser
     * @param  array  $filters  Validated inputs from FeedRequest
     * @return LengthAwarePaginator
     */
    public function getDiscoveryFeed(User $authUser, array $filters = []): LengthAwarePaginator
    {
        $cacheKey = $this->buildCacheKey($authUser->id, $filters);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($authUser, $filters) {
            return $this->buildFeedQuery($authUser, $filters)->paginate(self::PER_PAGE);
        });
    }

    /**
     * Invalidate all feed cache pages for a given user.
     * Useful when a user updates their profile, swipes, or gets matched.
     */
    public function invalidateUserFeedCache(string $userId): void
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis  = Cache::getRedis();
                $prefix = config('cache.prefix') . ':' . self::CACHE_PREFIX . $userId . ':*';
                $keys   = $redis->keys($prefix);

                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
        } catch (\Exception $e) {
            logger()->warning("FeedService: failed to invalidate feed cache for user {$userId}");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Query Builder
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Core feed query:
     *   1. Exclude self, already-swiped users, and existing matches.
     *   2. Apply optional filters (role, stage, radius, etc.).
     *   3. Compute compatibility score via SQL subqueries.
     *   4. Order by score DESC.
     */
    private function buildFeedQuery(User $authUser, array $filters)
    {
        $userId = $authUser->id;

        // ── 1. Collect excluded IDs ────────────────────────────────────────────
        // Users already swiped (connect or skip)
        $swipedIds = Like::where('from_user_id', $userId)
                         ->pluck('to_user_id')
                         ->toArray();

        // Users in existing active matches
        $matchedIds = UserMatch::where(function ($q) use ($userId) {
                            $q->where('user_id', $userId)
                              ->orWhere('matched_user_id', $userId);
                        })
                        ->where('status', 'active')
                        ->get()
                        ->flatMap(fn($m) => [$m->user_id, $m->matched_user_id])
                        ->unique()
                        ->toArray();

        $excludedIds = array_unique(array_merge([$userId], $swipedIds, $matchedIds));

        // ── 2. Scoring subqueries ──────────────────────────────────────────────
        // Interest overlap: shared tags / max(user_tags, target_tags)
        $interestScore = "
            COALESCE((
                SELECT ROUND(
                    COUNT(DISTINCT shared.tag_id) * 100.0
                    / NULLIF(GREATEST(
                        (SELECT COUNT(*) FROM user_tags WHERE user_id = '{$userId}'),
                        (SELECT COUNT(*) FROM user_tags WHERE user_id = users.id)
                    ), 0)
                , 2)
                FROM user_tags AS shared
                WHERE shared.user_id = '{$userId}'
                  AND shared.tag_id IN (
                    SELECT tag_id FROM user_tags WHERE user_id = users.id
                  )
            ), 0)
        ";

        // Role complement: 100 if different non-null role, 50 if same, 0 if null
        $authRole         = $authUser->role_category;
        $escapedAuthRole  = addslashes((string) $authRole);
        $roleScore = $authRole
            ? "CASE
                WHEN users.role_category IS NULL THEN 0
                WHEN users.role_category = '{$escapedAuthRole}' THEN 50
                ELSE 100
               END"
            : "0";

        // Location score (only when user has coordinates & radius filter)
        $hasLocation = $authUser->latitude && $authUser->longitude;
        $radius      = isset($filters['radius']) ? (float) $filters['radius'] : 50.0;

        $locationScore = $hasLocation
            ? "GREATEST(0, ROUND(100 - (
                    (6371 * acos(
                        cos(radians({$authUser->latitude}))
                        * cos(radians(users.latitude))
                        * cos(radians(users.longitude) - radians({$authUser->longitude}))
                        + sin(radians({$authUser->latitude})) * sin(radians(users.latitude))
                    ))
                    / {$radius} * 100
               ), 2))"
            : "0";

        $haversine = $hasLocation
            ? "(6371 * acos(
                    cos(radians({$authUser->latitude}))
                    * cos(radians(users.latitude))
                    * cos(radians(users.longitude) - radians({$authUser->longitude}))
                    + sin(radians({$authUser->latitude})) * sin(radians(users.latitude))
               ))"
            : "NULL";

        $compatibilityScore = "
            ROUND(
                (({$interestScore}) * 0.5)
                + (({$roleScore}) * 0.3)
                + (({$locationScore}) * 0.2)
            , 2)
        ";

        // ── 3. Base query ──────────────────────────────────────────────────────
        $query = User::select('users.*')
            ->selectRaw("{$haversine} AS distance_km")
            ->selectRaw("{$compatibilityScore} AS compatibility_score")
            ->whereNotIn('users.id', $excludedIds)
            ->where('users.is_active', true)
            ->where('users.is_onboarded', true);

        // ── 4. Optional filters ────────────────────────────────────────────────
        if (!empty($filters['role'])) {
            $query->where('users.role_category', $filters['role']);
        }

        if (!empty($filters['availability'])) {
            $query->where('users.commitment_level', $filters['availability']);
        }

        if (!empty($filters['stage'])) {
            $query->where('users.startup_stage', $filters['stage']);
        }

        // Industry filter via tags join
        if (!empty($filters['industry'])) {
            $industry = $filters['industry'];
            $query->whereExists(function ($sub) use ($userId, $industry) {
                $sub->selectRaw(1)
                    ->from('user_tags')
                    ->join('tags', 'tags.id', '=', 'user_tags.tag_id')
                    ->whereColumn('user_tags.user_id', 'users.id')
                    ->where('tags.name', 'like', "%{$industry}%");
            });
        }

        // Radius filter (Haversine HAVING clause)
        if ($hasLocation && $radius) {
            $query
                ->whereNotNull('users.latitude')
                ->whereNotNull('users.longitude')
                ->havingRaw("{$haversine} <= ?", [$radius]);
        }

        // ── 5. Order and return ────────────────────────────────────────────────
        return $query->orderByDesc('compatibility_score');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Cache Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build a deterministic cache key from userId + filters hash.
     * Pattern: connectx:feed:{userId}:{md5(sorted_filters+page)}
     */
    private function buildCacheKey(string $userId, array $filters): string
    {
        ksort($filters);
        $hash = md5(json_encode($filters));

        return self::CACHE_PREFIX . "{$userId}:{$hash}";
    }
}
