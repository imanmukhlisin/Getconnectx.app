<?php

namespace App\Services\Discovery;

use App\Models\DiscoveryCatalog;
use Illuminate\Support\Facades\Cache;
use App\Services\Discovery\CityCatalog;

class DiscoveryCatalogService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'connectx:discovery:catalogs:v3:';

    /**
     * Get grouped filter options for a discovery mode.
     *
     * @return array{city: array, industries: array, skills: array, roles: array, languages: array}
     */
    public function getFilterOptions(string $mode): array
    {
        $cacheKey = self::CACHE_PREFIX . $mode;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($mode) {
            // Helper to fetch and format options from onboarding_options with DYNAMIC GROUPING
            $fetchOnboardingOptions = function ($questionIds, $fallbackLabel) {
                if (is_string($questionIds)) $questionIds = [$questionIds];
                
                try {
                    $options = \Illuminate\Support\Facades\DB::table('onboarding_options')
                        ->whereIn('question_id', $questionIds)
                        ->orderBy('order_index')
                        ->get();

                    if ($options->isEmpty()) return [];

                    // Group by group_name column from database
                    $grouped = $options->groupBy(fn($opt) => $opt->group_name ?: $fallbackLabel);

                    $result = [];
                    foreach ($grouped as $groupName => $items) {
                        $result[] = [
                            'id'      => 'grp_' . \Illuminate\Support\Str::slug($groupName, '_'),
                            'label'   => $groupName,
                            'options' => $items->unique('value')->map(function ($opt) {
                                $labelStr = $opt->label ?? '';
                                $labels = json_decode($labelStr, true);
                                
                                $displayName = $opt->value;
                                if (json_last_error() === JSON_ERROR_NONE && is_array($labels)) {
                                    $displayName = $labels['id'] ?? $labels['en'] ?? $opt->value;
                                }

                                return [
                                    'id'    => $opt->value,
                                    'label' => $displayName,
                                ];
                            })->values()->toArray(),
                        ];
                    }
                    return $result;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Discovery Filter Error: " . $e->getMessage());
                    return [];
                }
            };

            // Mapping question IDs based on context
            $industryQ     = ['q_su_industry', 'q_fdr_industry', 'q_cf_industry', 'q_tm_industry'];
            $skillQ        = ['q_tm_skills', 'q_su_need_tm_skills', 'q_su_need_bt_tm'];
            
            // For finding_cofounder, 'roles' will represent 'Co-Founder Types' (Technical, Business, etc)
            $roleQ         = ['q_cf_type', 'q_fdr_cf_type', 'q_su_need_cf_type', 'q_bld_role'];
            
            $availabilityQ = ['q_cf_avail', 'q_fdr_cf_avail', 'q_tm_avail'];
            $equityQ       = ['q_cf_equity', 'q_su_equity_range'];

            return [
                'mode' => $mode,
                'city' => [
                    'id'          => 'q_city',
                    'type'        => 'searchable_dropdown',
                    'placeholder' => 'Search a city',
                    'required'    => true,
                    'meta'        => ['searchable' => true],
                    'options'     => CityCatalog::all(),
                ],
                'industries'   => $fetchOnboardingOptions($industryQ, 'Industries'),
                'roles'        => $fetchOnboardingOptions($roleQ, 'Co-Founder Type / Skill Strength'),
                'skills'       => $fetchOnboardingOptions($skillQ, 'Technical Skills'),
                'availability' => $fetchOnboardingOptions($availabilityQ, 'Availability'),
                'equity'       => $fetchOnboardingOptions($equityQ, 'Equity Commitment'),
                'languages'    => [
                    [
                        'id' => 'grp_languages',
                        'label' => 'Languages',
                        'options' => [
                            ['id' => 'id', 'label' => 'Bahasa Indonesia'],
                            ['id' => 'en', 'label' => 'English'],
                        ]
                    ]
                ],
            ];
        });
    }

    /**
     * Validate that submitted IDs exist in the onboarding options for the given type.
     */
    public function validateCatalogIds(array $ids, string $type, string $mode): void
    {
        if (empty($ids)) return;

        $questionIds = match ($type) {
            'industry'     => ['q_su_industry', 'q_fdr_industry', 'q_cf_industry', 'q_tm_industry'],
            'skill'        => ['q_tm_skills', 'q_su_need_tm_skills', 'q_su_need_bt_tm'],
            'role'         => ['q_cf_type', 'q_fdr_cf_type', 'q_su_need_cf_type', 'q_bld_role'],
            'availability' => ['q_cf_avail', 'q_fdr_cf_avail', 'q_tm_avail'],
            'equity'       => ['q_cf_equity', 'q_su_equity_range'],
            default        => [],
        };

        if (empty($questionIds)) return;

        $validIds = \Illuminate\Support\Facades\DB::table('onboarding_options')
            ->whereIn('question_id', $questionIds)
            ->pluck('value')
            ->toArray();

        $invalid = array_diff($ids, $validIds);

        if (!empty($invalid)) {
            throw new \InvalidArgumentException(
                "Unknown {$type} IDs: " . implode(', ', $invalid)
            );
        }
    }
}
