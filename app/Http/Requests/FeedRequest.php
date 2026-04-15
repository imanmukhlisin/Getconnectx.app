<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'page'         => 'sometimes|integer|min:1',
            'industry'     => 'sometimes|string|max:100',
            'role'         => 'sometimes|string|in:founder,co-founder,builder,engineer,designer,marketer,investor,advisor,operator',
            'availability' => 'sometimes|string|in:full-time,part-time,weekends,flexible',
            'location'     => 'sometimes|string|max:100',
            'stage'        => 'sometimes|string|in:idea,mvp,early-traction,growth,scaling',
            'radius'       => 'sometimes|numeric|min:1|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'role.in'         => 'Invalid role. Allowed: founder, co-founder, builder, engineer, designer, marketer, investor, advisor, operator.',
            'availability.in' => 'Invalid availability. Allowed: full-time, part-time, weekends, flexible.',
            'stage.in'        => 'Invalid stage. Allowed: idea, mvp, early-traction, growth, scaling.',
            'radius.max'      => 'Radius cannot exceed 500 km.',
        ];
    }
}
