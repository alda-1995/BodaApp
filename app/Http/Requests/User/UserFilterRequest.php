<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:255'],
            'status'   => ['nullable', 'string', 'in:no_template,pending_date,active,expiring,expired,suspended'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
