<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteCoadminRequest extends FormRequest
{
    /** Errores aparte: no se mezclan con los del formulario de configuración. */
    protected $errorBag = 'coadmin';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        $eventId = $this->user()->currentEvent()?->id;

        return [
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::notIn([mb_strtolower($this->user()->email)]),
                Rule::unique('event_coadmins', 'email')->where('event_id', $eventId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Escribe el correo de la persona que quieres invitar.',
            'email.email' => 'Ese correo no parece válido.',
            'email.not_in' => 'Ya eres administrador de tu evento.',
            'email.unique' => 'Ya invitaste a ese correo.',
        ];
    }
}
