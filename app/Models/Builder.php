<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Builder extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'role_category',
        'primary_role',
        'commitment_level',
        'work_arrangement',
        'remote_ready',
        'open_to_remote',
        'willing_to_relocate',
    ];

    protected function casts(): array
    {
        return [
            'remote_ready'        => 'boolean',
            'open_to_remote'      => 'boolean',
            'willing_to_relocate' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Get the User that owns this builder profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
