<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'code',
        'expires_at',
        'verified_at',
        'send_count',
        'send_window_start',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'        => 'datetime',
            'verified_at'       => 'datetime',
            'send_window_start' => 'datetime',
            'send_count'        => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Latest unverified OTP for a user and channel.
     */
    public function scopeLatestActiveFor($query, User $user, string $channel)
    {
        return $query
            ->where('user_id', $user->id)
            ->where('channel', $channel)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest();
    }

    /**
     * Check if OTP is still valid (not expired, not verified).
     */
    public function isValid(): bool
    {
        return $this->verified_at === null
            && $this->expires_at->isFuture();
    }
}
