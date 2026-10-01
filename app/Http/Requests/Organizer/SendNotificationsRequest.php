<?php

namespace App\Http\Requests\Organizer;

use App\Notifications\ChannelManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendNotificationsRequest extends FormRequest
{
    /**
     * La pertenencia de los invitados se garantiza en el controlador, que sólo
     * busca entre los del organizador autenticado.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_ids' => ['required', 'array', 'min:1'],
            'guest_ids.*' => ['integer'],
            // Sólo medios registrados y con credenciales configuradas.
            'channel' => ['required', 'string', Rule::in(app(ChannelManager::class)->availableKeys())],
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_ids.required' => 'Selecciona al menos un invitado.',
            'guest_ids.min' => 'Selecciona al menos un invitado.',
            'channel.required' => 'Elige por dónde enviar el mensaje.',
            'channel.in' => 'Ese medio de envío no está disponible.',
            'message.required' => 'Escribe el mensaje que quieres enviar.',
            'message.max' => 'El mensaje no debe exceder los :max caracteres.',
        ];
    }
}
