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

            'fcm_token'             => ['nullable', 'string'],
            'latitude'              => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'             => ['nullable', 'numeric', 'between:-180,180'],
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
            'email.required'         => __('messages.val_email_required'),
            'email.email'            => __('messages.val_email_format'),
            'email.max'              => __('messages.val_email_max'),
            'email.unique'           => __('messages.val_email_unique'),
            
            'password.required'      => __('messages.val_password_required'),
            'password.confirmed'     => __('messages.val_password_confirmed'),
            'password.min'           => __('messages.val_password_min'),
            'password.mixed'         => __('messages.val_password_mixed'),
            'password.numbers'       => __('messages.val_password_numbers'),
            'password.symbols'       => __('messages.val_password_symbols'),
            'password.uncompromised' => __('messages.val_password_uncompromised'),
            
            'password_confirmation.required' => __('messages.val_password_conf_required'),
        ];
    }
}
