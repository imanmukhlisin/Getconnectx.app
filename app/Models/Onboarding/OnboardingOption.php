<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingOption extends Model
{
    use HasFactory;

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
    ];

    public function question()
    {
        return $this->belongsTo(OnboardingQuestion::class, 'question_id');
    }
}
