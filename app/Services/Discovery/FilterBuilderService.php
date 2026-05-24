<?php

namespace App\Services\Discovery;

use App\Exceptions\PremiumRequiredException;
use App\Models\Like;
use App\Models\Startup;
use App\Models\User;
use App\Models\UserMatch;
use App\Services\Discovery\CityCatalog;
use Illuminate\Database\Eloquent\Builder;

class FilterBuilderService
{
    // ─── Premium filter keys per mode ─────────────────────────────────────────
    private const PREMIUM_FILTERS = [
        'finding_cofounder' => ['aiMatchPrecision', 'founderBuilderQuality', 'cofounderReadiness', 'globalCompatibility'],
        'building_team'     => ['aiTalentPrecision', 'executionQuality', 'globalCompatibility', 'hiringReadiness'],
        'explore_startups'  => ['startupQuality', 'startupReadiness', 'opportunityFit', 'aiStartupFit'],
        'joining_startups'  => ['founderQuality', 'leadershipStrength', 'startupReadiness', 'equityAndCommitment'],
    ];

    // ─── Constants ────────────────────────────────────────────────────────────

    // ═══════════════════════════════════════════════════════════════════
    //  Premium Validation
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Check if non-pro user is using premium filters. Throw if so.
     *
     * @throws PremiumRequiredException
     */
    public function validatePremiumFilters(User $user, array $filters, string $mode): void
    {
        if ($user->is_pro) return;

        $premiumKeys = self::PREMIUM_FILTERS[$mode] ?? [];

        foreach ($premiumKeys as $key) {
            if (isset($filters[$key]) && !empty($filters[$key])) {
                throw new PremiumRequiredException();
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Profile Query (P2P — finding_cofounder, building_team)
    // ═══════════════════════════════════════════════════════════════════

    public function buildProfileQuery(User $authUser, array $filters, string $mode): Builder
    {
        $userId = $authUser->id;
        $excludedIds = $this->getExcludedUserIds($userId);

        // ── P2P: Query users with their builders profile (LEFT JOIN) ─────
        $query = User::select('users.*')
            ->leftJoin('builders', 'builders.user_id', '=', 'users.id')
            ->whereNotIn('users.id', $excludedIds)
            ->where('users.is_active', true)
            ->where('users.is_onboarded', true)
            // ── Only show users who have LinkedIn data connected ──────────
            // Users without a user_credentials entry have incomplete profiles
            // and should not appear in the Discovery feed.
            ->whereExists(function ($sub) {
                $sub->selectRaw(1)
                    ->from('user_credentials')
                    ->whereColumn('user_credentials.user_id', 'users.id');
            });

        // ── Industry filter (via user_tags + tags join) ──────────────────────
        if (!empty($filters['industryIds'])) {
            $industryNames = $filters['industryIds'];
            $query->whereExists(function ($sub) use ($industryNames) {
                $sub->selectRaw(1)
                    ->from('user_tags')
                    ->join('tags', 'tags.id', '=', 'user_tags.tag_id')
                    ->whereColumn('user_tags.user_id', 'users.id')
                    ->where('tags.type', 'industry')
                    ->whereIn('tags.name', $industryNames);
            });
        }

        // ── Skill filter (via user_tags + tags join) ──────────────────────
        if (!empty($filters['skillIds'])) {
            $skillLabels = $filters['skillIds'];
            $query->whereExists(function ($sub) use ($skillLabels) {
                $sub->selectRaw(1)
                    ->from('user_tags')
                    ->join('tags', 'tags.id', '=', 'user_tags.tag_id')
                    ->whereColumn('user_tags.user_id', 'users.id')
                    ->where('tags.type', 'skill')
                    ->whereIn('tags.name', $skillLabels);
            });
        }

        // ── Role filter ──────────────────────────────────────────────────
        if (!empty($filters['roleNeededIds'])) {
            $roles = $filters['roleNeededIds'];
            $query->whereIn('builders.role_category', $roles);
        }

        // ── Skill strength filter ────────────────────────────────────────
        if (!empty($filters['skillStrengthIds'])) {
            $strengths = $filters['skillStrengthIds'];
            $query->whereIn('builders.role_category', $strengths);
        }

        // ── Commitment filter ────────────────────────────────────────────
        if (!empty($filters['commitmentIds'])) {
            $commitments = $filters['commitmentIds'];
            $query->whereIn('builders.commitment_level', $commitments);
        }

        // ── Location / Distance filter ────────────────────────────────────
        $this->applyLocationFilter($query, $authUser, $filters, 'users');

        // ── Work arrangement filter ──────────────────────────────────────
        if (!empty($filters['locationAvailability']['workArrangementIds'] ?? null)) {
            $arrangements = $filters['locationAvailability']['workArrangementIds'];
            $query->whereIn('builders.work_arrangement', $arrangements);
        }

        // ── Remote ready filter ──────────────────────────────────────────
        if (!empty($filters['locationAvailability']['remoteReady'] ?? false)) {
            $query->where('builders.remote_ready', true);
        }

        return $query;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Startup Query (P2B — explore_startups, joining_startups)
    // ═══════════════════════════════════════════════════════════════════

    public function buildStartupQuery(User $authUser, array $filters, string $mode): Builder
    {
        $userId = $authUser->id;
        $swipedIds = Like::where('from_user_id', $userId)->pluck('to_user_id')->toArray();

        $query = Startup::select('startups.*')
            ->where('startups.owner_id', '!=', $userId)
            ->whereNotIn('startups.owner_id', $swipedIds);

        // ── Industry filter ───────────────────────────────────────────────
        if (!empty($filters['industryIds'])) {
            $industries = $filters['industryIds'];
            $query->where(function ($q) use ($industries) {
                $q->whereIn('startups.industry', $industries)
                  ->orWhereIn('startups.secondary_industry', $industries);
            });
        }

        // ── Startup stage filter ──────────────────────────────────────────
        if (!empty($filters['startupStageIds'])) {
            $stages = $filters['startupStageIds'];
            $query->whereIn('startups.stage', $stages);
        }

        // ── Location / Distance filter ────────────────────────────────────
        $this->applyLocationFilter($query, $authUser, $filters, 'startups');

        return $query;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Cursor Pagination
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Apply cursor-based pagination and return structured result.
     *
     * @return array{items: \Illuminate\Support\Collection, nextCursor: string|null, hasMore: bool}
     */
    public function applyCursorPagination(Builder $query, ?string $cursor, int $limit): array
    {
        $table = $query->getModel()->getTable();

        if ($cursor) {
            $query->where("{$table}.id", '>', $cursor);
        }

        $query->orderBy("{$table}.id", 'asc');

        // Fetch limit + 1 to determine hasMore
        $results = $query->limit($limit + 1)->get();
        $hasMore = $results->count() > $limit;

        if ($hasMore) {
            $results = $results->take($limit);
        }

        $nextCursor = $hasMore ? $results->last()?->id : null;

        return [
            'items'      => $results,
            'nextCursor' => $nextCursor,
            'hasMore'    => $hasMore,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Get user IDs to exclude from feed (self + already swiped + matched).
     */
    private function getExcludedUserIds(string $userId): array
    {
        $swipedIds = Like::where('from_user_id', $userId)->pluck('to_user_id')->toArray();

        $matchedIds = UserMatch::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('matched_user_id', $userId);
        })
        ->where('status', 'active')
        ->get()
        ->flatMap(fn($m) => [$m->user_id, $m->matched_user_id])
        ->unique()
        ->toArray();

        return array_unique(array_merge([$userId], $swipedIds, $matchedIds));
    }

    /**
     * Apply Haversine distance filter if location data is provided.
     */
    private function applyLocationFilter(Builder $query, User $authUser, array $filters, string $table): void
    {
        $locFilter = $filters['locationAvailability'] ?? [];
        $lat = $locFilter['latitude'] ?? $authUser->latitude;
        $lng = $locFilter['longitude'] ?? $authUser->longitude;
        $radiusKm = $locFilter['distanceKm'] ?? null;
        $city = $locFilter['city'] ?? null;

        if ($city) {
            if (!in_array($city, CityCatalog::values())) {
                throw new \InvalidArgumentException("Unknown city: {$city}");
            }
            $query->where("{$table}.city", $city);
        }

        if ($lat && $lng) {
            $haversine = sprintf(
                '(6371 * acos(cos(radians(%F)) * cos(radians(%s.latitude)) * cos(radians(%s.longitude) - radians(%F)) + sin(radians(%F)) * sin(radians(%s.latitude))))',
                $lat, $table, $table, $lng, $lat, $table
            );

            $query->select($table . '.*')
                  ->selectRaw("{$haversine} AS distance_km");

            if ($radiusKm) {
                $query->where(function ($q) use ($haversine, $radiusKm, $table) {
                    $q->whereRaw("{$haversine} <= ?", [(float)$radiusKm])
                      ->orWhereNull("{$table}.latitude")
                      ->orWhereNull("{$table}.longitude");
                });
            }
        }
    }


}
