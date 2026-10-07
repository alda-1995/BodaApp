<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventValidityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La ruta ya exige el rol de superadmin.
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Una casilla sin marcar no se envía: se interpreta como "no".
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            /*
            | Sin fecha la invitación no vence nunca. Es un caso real —una boda
            | que todavía no tiene fecha— así que se permite vaciarla.
            */
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            // Por qué se tocó: lo que verá quien revise esto dentro de un año.
            'reason' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'expires_at.date' => 'La fecha de vencimiento no es una fecha válida.',
        ];
    }
}
