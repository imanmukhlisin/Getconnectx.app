<?php

namespace App\Services\Discovery;

use App\Models\DiscoveryCatalog;
use Illuminate\Support\Facades\Cache;
use App\Services\Discovery\CityCatalog;

class DiscoveryCatalogService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'connectx:discovery:catalogs:';

    /**
     * Get grouped filter options for a discovery mode.
     *
     * @return array{city: array, industries: array, skills: array, roles: array, languages: array}
     */
    public function getFilterOptions(string $mode): array
    {
        $cacheKey = self::CACHE_PREFIX . $mode;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($mode) {
            // Helper to fetch and format options from onboarding_options
            $fetchOnboardingOptions = function ($questionId, $groupLabel) {
                try {
                    $options = \Illuminate\Support\Facades\DB::table('onboarding_options')
                        ->where('question_id', $questionId)
                        ->orderBy('sort_order')
                        ->get();

                    if ($options->isEmpty()) return [];

                    return [
                        [
                            'id'      => 'grp_' . $questionId,
                            'label'   => $groupLabel,
                            'options' => $options->map(function ($opt) {
                                $labelStr = $opt->label ?? '';
                                $labels = json_decode($labelStr, true);
                                
                                // Fallback if JSON is invalid or label is empty
                                $displayName = $opt->value;
                                if (json_last_error() === JSON_ERROR_NONE && is_array($labels)) {
                                    $displayName = $labels['id'] ?? $labels['en'] ?? $opt->value;
                                }

                                return [
                                    'id'    => $opt->value,
                                    'label' => $displayName,
                                ];
                            })->values()->toArray(),
                        ]
                    ];
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Discovery Filter Error for {$questionId}: " . $e->getMessage());
                    return [];
                }
            };

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
                'industries' => $fetchOnboardingOptions('q_su_industry', 'All Industries'),
                'skills'     => $fetchOnboardingOptions('q_bld_skills', 'All Skills'),
                'roles'      => $fetchOnboardingOptions('q_bld_role', 'Available Roles'),
                'languages'  => $fetchOnboardingOptions('q_languages', 'Languages'),
            ];
        });
    }

    /**
     * Validate that submitted IDs exist in the onboarding options for the given type.
     */
    public function validateCatalogIds(array $ids, string $type, string $mode): void
    {
        if (empty($ids)) return;

        $questionId = match ($type) {
            'industry' => 'q_su_industry',
            'skill'    => 'q_bld_skills',
            'role'     => 'q_bld_role',
            'language' => 'q_languages',
            default    => null,
        };

        if (!$questionId) return;

        $validIds = \Illuminate\Support\Facades\DB::table('onboarding_options')
            ->where('question_id', $questionId)
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
