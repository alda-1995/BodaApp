<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Lo que manda el formulario de confirmación de la invitación pública.
 *
 * El tope de lugares no se valida aquí: depende del invitado o del enlace
 * abierto, y eso lo sabe el controlador.
 */
class StoreRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => ['nullable', 'uuid'],
            // Sin uuid viene por el enlace abierto y hay que saber quién es.
            'name' => ['required_without:uuid', 'nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'attendance' => ['required', 'in:confirmed,declined'],
            'passes' => ['required_if:attendance,confirmed', 'nullable', 'integer', 'min:1'],
            'dietary_restrictions' => ['nullable', 'string', 'max:255'],
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'phone' => 'celular',
            'email' => 'correo electrónico',
            'attendance' => 'asistencia',
            'passes' => 'número de asistentes',
            'dietary_restrictions' => 'restricciones alimentarias',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_without' => 'Escribe tu nombre para confirmar.',
            'attendance.required' => 'Dinos si nos acompañas.',
            'attendance.in' => 'La respuesta de asistencia no es válida.',
            'passes.required_if' => 'Indica cuántas personas asistirán.',
            'passes.min' => 'Debe confirmarse al menos un lugar.',
        ];
    }
}
