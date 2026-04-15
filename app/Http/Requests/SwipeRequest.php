<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'to_user_id' => 'required|uuid|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'to_user_id.required' => 'A target user must be specified.',
            'to_user_id.uuid'     => 'The target user ID must be a valid UUID.',
            'to_user_id.exists'   => 'The specified user does not exist.',
        ];
    }
}
