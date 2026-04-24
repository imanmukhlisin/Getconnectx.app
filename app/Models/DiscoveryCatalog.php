<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscoveryCatalog extends Model
{
    protected $table = 'discovery_catalogs';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'group_id',
        'group_label',
        'label',
        'modes',
        'is_premium',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'modes'      => 'array',
            'is_premium' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Filter catalogs available for a given discovery mode.
     */
    public function scopeForMode($query, string $mode)
    {
        return $query->whereJsonContains('modes', $mode);
    }

    /**
     * Filter by catalog type (industry, skill, role, language).
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
