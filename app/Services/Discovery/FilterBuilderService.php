<?php

namespace App\Services\Discovery;

use App\Exceptions\PremiumRequiredException;
use App\Models\Like;
use App\Models\Startup;
use App\Models\User;
use App\Models\UserMatch;
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

    // ─── Catalog ID → DB value mapping ────────────────────────────────────────
    private const INDUSTRY_MAP = [
        'ind_ai' => 'AI', 'ind_fintech' => 'Fintech', 'ind_healthtech' => 'Healthtech',
        'ind_edtech' => 'Edtech', 'ind_web3' => 'Web3', 'ind_saas' => 'SaaS',
    ];

    private const COMMITMENT_MAP = [
        'commitment_full_time'    => 'full-time',
        'commitment_part_time'    => 'part-time',
        'commitment_side_project' => 'side-project',
    ];

    private const WORK_ARRANGEMENT_MAP = [
        'wa_onsite' => 'onsite',
        'wa_hybrid' => 'hybrid',
        'wa_remote' => 'remote',
    ];

    private const STAGE_MAP = [
        'stage_idea'     => 'idea',
        'stage_mvp'      => 'mvp',
        'stage_pre_seed' => 'pre-seed',
        'stage_seed'     => 'seed',
    ];

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

        $query = User::select('users.*')
            ->whereNotIn('users.id', $excludedIds)
            ->where('users.is_active', true)
            ->where('users.is_onboarded', true);

        // ── Industry filter (via user_tags + tags join) ───────────────────
        if (!empty($filters['industryIds'])) {
            $industryNames = $this->mapIds($filters['industryIds'], self::INDUSTRY_MAP);
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
            $skillLabels = $this->mapIds($filters['skillIds'], $this->buildSkillMap());
            $query->whereExists(function ($sub) use ($skillLabels) {
                $sub->selectRaw(1)
                    ->from('user_tags')
                    ->join('tags', 'tags.id', '=', 'user_tags.tag_id')
                    ->whereColumn('user_tags.user_id', 'users.id')
                    ->where('tags.type', 'skill')
                    ->whereIn('tags.name', $skillLabels);
            });
        }

        // ── Role filter ───────────────────────────────────────────────────
        if (!empty($filters['roleNeededIds'])) {
            $roles = $this->mapIds($filters['roleNeededIds'], $this->buildRoleMap());
            $query->whereIn('users.role_category', $roles);
        }

        // ── Skill strength filter (finding_cofounder mode) ────────────────
        if (!empty($filters['skillStrengthIds'])) {
            $strengths = $this->mapIds($filters['skillStrengthIds'], $this->buildSkillStrengthMap());
            $query->whereIn('users.role_category', $strengths);
        }

        // ── Commitment filter ─────────────────────────────────────────────
        if (!empty($filters['commitmentIds'])) {
            $commitments = $this->mapIds($filters['commitmentIds'], self::COMMITMENT_MAP);
            $query->whereIn('users.commitment_level', $commitments);
        }

        // ── Location / Distance filter ────────────────────────────────────
        $this->applyLocationFilter($query, $authUser, $filters, 'users');

        // ── Work arrangement filter ───────────────────────────────────────
        if (!empty($filters['locationAvailability']['workArrangementIds'] ?? null)) {
            $arrangements = $this->mapIds(
                $filters['locationAvailability']['workArrangementIds'],
                self::WORK_ARRANGEMENT_MAP
            );
            $query->whereIn('users.work_arrangement', $arrangements);
        }

        // ── Remote ready filter ───────────────────────────────────────────
        if (!empty($filters['locationAvailability']['remoteReady'] ?? false)) {
            $query->where('users.remote_ready', true);
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
            $industries = $this->mapIds($filters['industryIds'], self::INDUSTRY_MAP);
            $query->where(function ($q) use ($industries) {
                $q->whereIn('startups.industry', $industries)
                  ->orWhereIn('startups.secondary_industry', $industries);
            });
        }

        // ── Startup stage filter ──────────────────────────────────────────
        if (!empty($filters['startupStageIds'])) {
            $stages = $this->mapIds($filters['startupStageIds'], self::STAGE_MAP);
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
        if ($cursor) {
            $query->where($query->getModel()->getTable() . '.id', '>', $cursor);
        }

        $query->orderBy($query->getModel()->getTable() . '.id', 'asc');

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

        if ($lat && $lng) {
            $haversine = sprintf(
                '(6371 * acos(cos(radians(%F)) * cos(radians(%s.latitude)) * cos(radians(%s.longitude) - radians(%F)) + sin(radians(%F)) * sin(radians(%s.latitude))))',
                $lat, $table, $table, $lng, $lat, $table
            );

            $query->whereNotNull("{$table}.latitude")
                  ->whereNotNull("{$table}.longitude")
                  ->selectRaw("{$haversine} AS distance_km");

            if ($radiusKm) {
                $query->havingRaw("{$haversine} <= ?", [(float)$radiusKm]);
            }
        }
    }

    /**
     * Map canonical IDs to database values.
     */
    private function mapIds(array $ids, array $map): array
    {
        return array_values(array_filter(array_map(fn($id) => $map[$id] ?? null, $ids)));
    }

    private function buildSkillMap(): array
    {
        return [
            'skill_react' => 'React', 'skill_python' => 'Python', 'skill_figma' => 'Figma',
            'skill_growth' => 'Growth', 'skill_seo' => 'SEO', 'skill_salesforce' => 'Salesforce',
        ];
    }

    private function buildRoleMap(): array
    {
        return [
            'role_engineer' => 'engineer', 'role_product' => 'product', 'role_designer' => 'designer',
            'role_sales' => 'sales', 'role_marketing' => 'marketing', 'role_operations' => 'operations',
            'role_finance' => 'finance', 'role_growth' => 'growth', 'role_ai_ml' => 'ai-ml',
        ];
    }

    private function buildSkillStrengthMap(): array
    {
        return [
            'ss_technical' => 'engineer', 'ss_product' => 'product', 'ss_business' => 'business',
            'ss_sales' => 'sales', 'ss_marketing' => 'marketing', 'ss_design' => 'designer',
            'ss_operations' => 'operations', 'ss_finance' => 'finance',
        ];
    }
}
