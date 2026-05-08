<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StartupInvitation extends Model
{
    use HasUuids;

    protected $fillable = [
        'startup_id',
        'sender_id',
        'recipient_email',
        'role_id',
        'equity_percent',
        'commitment',
        'status',
        'expires_at',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'equity_percent' => 'float',
            'expires_at' => 'datetime',
            'acted_at' => 'datetime',
        ];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
