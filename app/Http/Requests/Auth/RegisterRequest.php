<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type'           => ['required', 'string', 'in:talent,startup'],
            'email'                 => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
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
            'entity_type.required'   => 'Tipe entitas wajib dipilih (talent atau startup).',
            'entity_type.in'         => 'Tipe entitas hanya boleh "talent" atau "startup".',
            
            'email.required'         => 'Alamat email wajib diisi.',
            'email.email'            => 'Format email tidak valid (contoh yang benar: nama@email.com).',
            'email.max'              => 'Email terlalu panjang (maksimal 255 karakter).',
            'email.unique'           => 'Email ini sudah terdaftar. Silakan gunakan email lain atau login.',
            
            'password.required'      => 'Password wajib diisi.',
            'password.confirmed'     => 'Konfirmasi password tidak cocok dengan password yang diketik.',
            'password.min'           => 'Password harus berisi minimal 8 karakter.',
            'password.mixed'         => 'Password harus mengandung minimal 1 huruf kapital dan 1 huruf kecil.',
            'password.numbers'       => 'Password harus mengandung minimal 1 angka.',
            'password.symbols'       => 'Password harus mengandung minimal 1 simbol / karakter unik (@, #, !, dsb).',
            'password.uncompromised' => 'Password Anda terlalu umum / pernah bocor di internet. Silakan buat kombinasi password yang lebih aman dan unik.',
            
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
        ];
    }
}
