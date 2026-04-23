<?php

namespace App\Models\Onboarding;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingStep extends Model
{
    use HasFactory, HasUuids, HasTranslations;

    protected $table = 'onboarding_steps';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'flow_id',
        'order_index',
        'section',
        'title',
        'subtitle',
        'cta_label',
        'auto_advance',
        'can_go_back',
    ];

    protected $casts = [
        'auto_advance' => 'boolean',
        'can_go_back' => 'boolean',
        'order_index' => 'integer',
        'title' => 'array',
        'subtitle' => 'array',
        'cta_label' => 'array',
    ];

    public function flow()
    {
        return $this->belongsTo(OnboardingFlow::class, 'flow_id');
    }

    public function questions()
    {
        return $this->hasMany(OnboardingQuestion::class, 'step_id')->orderBy('order_index');
    }

    public function transitions()
    {
        return $this->hasMany(OnboardingTransition::class, 'from_step_id')->orderByDesc('priority');
    }
}
