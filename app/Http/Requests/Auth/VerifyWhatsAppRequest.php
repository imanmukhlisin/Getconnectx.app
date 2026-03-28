<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyWhatsAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp_code' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp_code.required' => __('messages.val_otp_required'),
            'otp_code.digits'   => __('messages.val_otp_digits'),
        ];
    }
}
