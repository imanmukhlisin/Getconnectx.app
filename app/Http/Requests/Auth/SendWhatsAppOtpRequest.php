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
        $user = $this->user();
        
        return [
            // Format internasional: +[kode negara][nomor], contoh: +6281234567890
            'whatsapp_number' => [
                $user && $user->whatsapp_number ? 'nullable' : 'required',
                'string',
                'regex:/^\+[1-9]\d{7,14}$/',
                'max:16',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp_number.required' => __('messages.val_wa_number_required'),
            'whatsapp_number.regex'    => __('messages.val_wa_number_regex'),
        ];
    }
}
