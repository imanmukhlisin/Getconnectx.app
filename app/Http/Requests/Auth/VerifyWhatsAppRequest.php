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
            'otp_code.required' => 'Kode OTP WhatsApp wajib diisi.',
            'otp_code.digits'   => 'Kode OTP WhatsApp harus berupa 6 digit angka.',
        ];
    }
}
