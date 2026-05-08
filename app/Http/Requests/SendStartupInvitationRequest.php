<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendStartupInvitationRequest extends FormRequest
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
            'user_id' => 'nullable|string|uuid',
            'email' => 'required_without:user_id|email|nullable',
            'roleId' => 'nullable|string', // Support roleId
            'role' => 'nullable|string', // Support role from old contract
            'equityPercent' => 'required|numeric|min:0|max:100',
            'commitment' => 'required|string|in:full_time,part_time,advisor',
        ];
    }
}
