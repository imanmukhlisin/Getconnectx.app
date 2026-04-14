<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchScore extends Model
{
    protected $table = 'match_scores';

    protected $fillable = [
        'match_id',
        'score',
        'label',
        'insight',
    ];

    public function match()
    {
        return $this->belongsTo(UserMatch::class, 'match_id');
    }
}
