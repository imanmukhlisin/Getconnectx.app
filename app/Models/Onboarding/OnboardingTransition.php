<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingTransition extends Model
{
    use HasFactory;

    protected $table = 'onboarding_transitions';

    protected $fillable = [
        'from_step_id',
        'condition',
        'to_step_id',
        'to_flow_id',
        'priority',
    ];

    protected $casts = [
        'condition' => 'array',
        'priority' => 'integer',
    ];

    public function fromStep()
    {
        return $this->belongsTo(OnboardingStep::class, 'from_step_id');
    }

    public function toStep()
    {
        return $this->belongsTo(OnboardingStep::class, 'to_step_id');
    }

    public function toFlow()
    {
        return $this->belongsTo(OnboardingFlow::class, 'to_flow_id');
    }
}
