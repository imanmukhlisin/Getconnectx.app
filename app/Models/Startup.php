<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Startup extends Model
{
    use HasUuids;

    protected $fillable = [
        'owner_id',
        'name',
        'tagline',
        'stage',
        'industry',
        'secondary_industry',
        'team_size',
        'latitude',
        'longitude',
        'city',
        'country',
        'description',
        'logo_url',
        'looking_for',
        'open_roles',
    ];

    protected function casts(): array
    {
        return [
            'looking_for' => 'array',
            'open_roles'  => 'array',
            'team_size'   => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Filter startups within a given radius (km) from a reference point.
     */
    public function scopeInRadius($query, float $lat, float $lng, float $radiusKm = 50)
    {
        $haversine = sprintf(
            '(6371 * acos(cos(radians(%F)) * cos(radians(latitude)) * cos(radians(longitude) - radians(%F)) + sin(radians(%F)) * sin(radians(latitude))))',
            $lat, $lng, $lat
        );

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("startups.*, {$haversine} AS distance_km")
            ->havingRaw("{$haversine} <= ?", [$radiusKm])
            ->orderByRaw("{$haversine} ASC");
    }
}
