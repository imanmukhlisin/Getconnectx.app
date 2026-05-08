<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StartupMember extends Model
{
    use HasUuids;

    protected $fillable = [
        'startup_id',
        'user_id',
        'role_id',
        'equity_percent',
        'commitment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'equity_percent' => 'float',
        ];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
