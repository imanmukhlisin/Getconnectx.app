<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchAnalysis extends Model
{
    protected $table = 'match_analyses';

    protected $fillable = [
        'match_id',
        'analysis_json',
        'generated_at',
    ];

    protected $casts = [
        'analysis_json' => 'array',
        'generated_at' => 'datetime',
    ];

    public function match()
    {
        return $this->belongsTo(UserMatch::class, 'match_id');
    }
}
