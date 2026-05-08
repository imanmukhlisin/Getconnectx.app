<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roleId' => 'sometimes|string',
            'equityPercent' => 'sometimes|numeric|min:0|max:100',
            'commitment' => 'sometimes|string|in:full_time,part_time,advisor',
            'status' => 'sometimes|string|in:active,pending',
        ];
    }
}
