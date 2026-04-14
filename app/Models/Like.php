<?php

namespace App\Models;

use App\Traits\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasPrefixedId;

    protected $table = 'likes';
    protected $idPrefix = 'like_';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'is_mutual',
    ];

    protected $casts = [
        'is_mutual' => 'boolean',
    ];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
