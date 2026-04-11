<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingResponse extends Model
{
    use HasFactory;

    protected $table = 'onboarding_responses';

    protected $fillable = [
        'session_id',
        'step_id',
        'question_id',
        'value',
        'answered_at',
    ];

    protected $casts = [
        'value' => 'array',
        'answered_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(OnboardingSession::class, 'session_id');
    }

    public function step()
    {
        return $this->belongsTo(OnboardingStep::class, 'step_id');
    }

    public function question()
    {
        return $this->belongsTo(OnboardingQuestion::class, 'question_id');
    }
}
