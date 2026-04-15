<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'                 => ['required', 'email:rfc,dns', 'max:255'],
            'token'                 => ['required', 'string', 'min:64'],
            'password'              => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'password_confirmation' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'                 => 'Email wajib diisi.',
            'email.email'                    => 'Format email tidak valid.',
            'token.required'                 => 'Token reset wajib disertakan.',
            'token.min'                      => 'Token reset tidak valid.',
            'password.required'              => 'Password baru wajib diisi.',
            'password.confirmed'             => 'Konfirmasi password tidak cocok.',
            'password.uncompromised'         => 'Password tersebut telah muncul dalam kebocoran data (data leak) publik. Demi keamanan Anda, silakan pilih password yang berbeda.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
        ];
    }
}
