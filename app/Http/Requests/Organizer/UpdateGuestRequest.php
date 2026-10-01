<?php

namespace App\Http\Requests\Organizer;

use App\DTOs\Guest\GuestDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuestRequest extends FormRequest
{
    /**
     * La pertenencia del invitado la garantiza GuestService::findForUser() (404).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = GuestDTO::rules();

        // Único por organizador, no en toda la tabla: dos organizadores pueden
        // invitar a la misma persona.
        $rules['email'][] = Rule::unique('guests', 'email')
            ->where('user_id', $this->user()->id)
            ->ignore($this->route('guest'));

        return $rules;
    }

    public function messages(): array
    {
        return GuestDTO::messages();
    }
}
