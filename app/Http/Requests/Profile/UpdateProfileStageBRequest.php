<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileStageBRequest extends FormRequest
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
            'industry_tag_ids'   => ['required', 'array', 'min:1'],
            'industry_tag_ids.*' => ['exists:tags,id'],
            'skill_tag_ids'      => ['required', 'array', 'min:1'],
            'skill_tag_ids.*'    => ['exists:tags,id'],
            'commitment_level'   => ['required', 'string', 'in:Full-time,Part-time,Side Project,Open to Discussion'],
            'startup_stage'      => ['nullable', 'string', 'in:Idea,MVP,Beta,Early Traction,Scaling'],
        ];
    }
}
