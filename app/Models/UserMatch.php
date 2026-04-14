<?php

namespace App\Models;

use App\Traits\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;

class UserMatch extends Model
{
    use HasPrefixedId;

    protected $table = 'matches';
    protected $idPrefix = 'mtc_';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'matched_user_id',
        'status',
        'matched_at',
        'expires_at',
        'conversation_id',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function matchedUser()
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function scores()
    {
        return $this->hasMany(MatchScore::class, 'match_id');
    }

    public function analysis()
    {
        return $this->hasOne(MatchAnalysis::class, 'match_id');
    }
}
