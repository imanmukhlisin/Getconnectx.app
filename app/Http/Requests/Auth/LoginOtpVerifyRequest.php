<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginOtpVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'exists:users,email'],
            'otp_code' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => __('messages.val_email_required'),
            'email.email'       => __('messages.val_email_format'),
            'email.exists'      => __('messages.val_email_not_found'),
            'otp_code.required' => __('messages.val_otp_required'),
            'otp_code.digits'   => __('messages.val_otp_digits'),
        ];
    }
}
