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
            $catalogs = DiscoveryCatalog::forMode($mode)
                ->orderBy('sort_order')
                ->get();

            $result = [
                'city' => [
                    'id'          => 'q_city',
                    'type'        => 'searchable_dropdown',
                    'placeholder' => 'Search a city',
                    'required'    => true,
                    'meta'        => ['searchable' => true],
                    'options'     => CityCatalog::all(),
                ],
                'industries' => [],
                'skills'     => [],
                'roles'      => [],
                'languages'  => [],
            ];

            $typeMap = [
                'industry' => 'industries',
                'skill'    => 'skills',
                'role'     => 'roles',
                'language' => 'languages',
            ];

            // Group catalogs by type + group_id
            $grouped = $catalogs->groupBy(fn($item) => $item->type . '|' . $item->group_id);

            foreach ($grouped as $key => $items) {
                [$type, $groupId] = explode('|', $key);
                $collectionKey = $typeMap[$type] ?? null;

                if (!$collectionKey) continue;

                $first = $items->first();

                $result[$collectionKey][] = [
                    'id'      => $groupId,
                    'label'   => $first->group_label,
                    'options' => $items->map(fn($item) => [
                        'id'    => $item->id,
                        'label' => $item->label,
                    ])->values()->toArray(),
                ];
            }

            return $result;
        });
    }

    /**
     * Validate that submitted IDs exist in the catalog for the given mode.
     *
     * @throws \InvalidArgumentException
     */
    public function validateCatalogIds(array $ids, string $type, string $mode): void
    {
        if (empty($ids)) return;

        $validIds = DiscoveryCatalog::forMode($mode)
            ->ofType($type)
            ->pluck('id')
            ->toArray();

        $invalid = array_diff($ids, $validIds);

        if (!empty($invalid)) {
            throw new \InvalidArgumentException(
                "Unknown {$type} IDs: " . implode(', ', $invalid)
            );
        }
    }
}
