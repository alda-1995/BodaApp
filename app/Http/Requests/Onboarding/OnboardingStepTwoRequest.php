<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingStepTwoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'partner_1_name' => ['required', 'string', 'max:100'],
            'partner_2_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tu nombre es obligatorio.',
            'partner_1_name.required' => 'El nombre del primer novio/a es obligatorio.',
            'partner_2_name.required' => 'El nombre del segundo novio/a es obligatorio.',
        ];
    }
}
