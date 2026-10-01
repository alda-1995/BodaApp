<?php

namespace App\Http\Requests\Order;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ProcessIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_id' => 'required|exists:templates,id',
            'email' => 'required|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => 'El identificador de la plantilla es obligatorio.',
            'template_id.exists' => 'La plantilla seleccionada no es válida.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Por favor, ingresa una dirección de correo válida.',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('email')) {
                return;
            }

            $email = $this->input('email');
            $user = User::where('email', $email)->first();

            if ($user) {
                // Una invitación a la vez: al vencer (o desactivarse) puede comprar otra.
                $hasActiveEvent = Event::where('user_id', $user->id)->available()->exists();

                if ($hasActiveEvent) {
                    $validator->errors()->add(
                        'email',
                        'Esta cuenta ya tiene una invitación digital activa. Podrás comprar otra cuando venza.'
                    );
                }
            }
        });
    }
}
