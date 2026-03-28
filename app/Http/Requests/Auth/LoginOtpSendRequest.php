<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginOtpSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('messages.val_email_required'),
            'email.email'    => __('messages.val_email_format'),
            'email.exists'   => __('messages.val_email_not_found'),
        ];
    }
}
