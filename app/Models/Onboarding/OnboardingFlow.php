<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingFlow extends Model
{
    use HasFactory;

    protected $table = 'onboarding_flows';
    
    // Primary key is string, not auto-incrementing
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'description',
        'is_entry',
    ];

    protected $casts = [
        'is_entry' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(OnboardingStep::class, 'flow_id')->orderBy('order_index');
    }
}
