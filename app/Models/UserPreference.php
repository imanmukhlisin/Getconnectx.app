<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $table = 'user_preferences';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'work_style',
    ];

    protected $casts = [
        'work_style' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
