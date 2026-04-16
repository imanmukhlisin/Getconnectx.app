<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'onboarding_questions';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'step_id',
        'order_index',
        'type',
        'label',
        'sub_label',
        'helper_text',
        'placeholder',
        'required',
        'validation',
        'depends_on',
        'meta',
    ];

    protected $casts = [
        'required' => 'boolean',
        'order_index' => 'integer',
        'validation' => 'array',
        'depends_on' => 'array',
        'meta' => 'array',
        'label' => 'array',
        'sub_label' => 'array',
        'helper_text' => 'array',
        'placeholder' => 'array',
    ];

    public function step()
    {
        return $this->belongsTo(OnboardingStep::class, 'step_id');
    }

    public function options()
    {
        return $this->hasMany(OnboardingOption::class, 'question_id')->orderBy('order_index');
    }
}
