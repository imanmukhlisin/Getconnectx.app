<?php

namespace App\Models;

use App\Traits\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasPrefixedId;

    // ─── Type Constants ───────────────────────────────────────────────────────
    /** Swipe right — user wants to connect */
    public const TYPE_CONNECT = 'connect';

    /** Swipe left — user is skipping */
    public const TYPE_SKIP = 'skip';

    protected $table = 'likes';
    protected $idPrefix = 'like_';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'is_mutual',
        'type',
    ];

    protected $casts = [
        'is_mutual' => 'boolean',
        'type'      => 'string',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    // ─── Query Scopes ─────────────────────────────────────────────────────────

    /** Only "connect" (swipe-right) interactions */
    public function scopeConnects($query)
    {
        return $query->where('type', self::TYPE_CONNECT);
    }

    /** Only "skip" (swipe-left) interactions */
    public function scopeSkips($query)
    {
        return $query->where('type', self::TYPE_SKIP);
    }
}
