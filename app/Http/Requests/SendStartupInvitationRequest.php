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
            'email' => 'required|email',
            'roleId' => 'required|string',
            'equityPercent' => 'required|numeric|min:0|max:100',
            'commitment' => 'required|string|in:full_time,part_time,advisor',
        ];
    }
}
