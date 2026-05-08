<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StartupApplication extends Model
{
    use HasUuids;

    protected $fillable = [
        'startup_id',
        'user_id',
        'role_id',
        'status',
    ];

    public function startup()
    {
        return $this->belongsTo(Startup::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
