<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OnboardingOption extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'onboarding_options';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'question_id',
        'order_index',
        'label',
        'sub_label',
        'value',
        'icon',
        'group_name',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'label' => 'array',
        'sub_label' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(OnboardingQuestion::class, 'question_id');
    }
}
