<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Format internasional: +[kode negara][nomor], contoh: +6281234567890
            'whatsapp_number' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{7,14}$/',
                'max:16',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.regex'    => 'Nomor WhatsApp harus menggunakan format internasional, contoh: +6281234567890.',
        ];
    }
}
