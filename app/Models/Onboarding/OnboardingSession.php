<?php

namespace App\Models\Onboarding;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingSession extends Model
{
    use HasFactory;

    protected $table = 'onboarding_sessions';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'current_step_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentStep()
    {
        return $this->belongsTo(OnboardingStep::class, 'current_step_id');
    }

    public function responses()
    {
        return $this->hasMany(OnboardingResponse::class, 'session_id');
    }
}
