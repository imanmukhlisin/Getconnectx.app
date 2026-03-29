<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileStageARequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'username'      => ['required', 'string', 'alpha_dash', 'max:30', 'unique:users,username,' . $this->user()?->id],
            'position'      => ['nullable', 'string', 'max:255'],
            'role_category' => ['required', 'string', 'in:Founder,Co-Founder,Team Member,Startup'],
        ];
    }
}
