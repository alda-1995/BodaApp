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

        /*
        | Único dentro de ESTA boda, no en toda la tabla ni en todo lo que el
        | organizador haya invitado alguna vez.
        |
        |   · en toda la tabla  → dos organizadores no podrían invitar a la
        |                         misma persona
        |   · por organizador   → a quien invitaste a tu boda anterior no lo
        |                         podrías volver a invitar a la nueva, que es
        |                         justo lo que uno quiere hacer
        |
        | La pertenencia a la boda se mira en event_guest, que es la tabla que
        | une invitado con evento.
        */
        $evento = $this->user()->currentEvent()?->id ?? 0;

        $rules['email'][] = Rule::unique('guests', 'email')
            ->where('user_id', $this->user()->id)
            ->where(fn ($query) => $query->whereExists(
                fn ($existe) => $existe->from('event_guest')
                    ->whereColumn('event_guest.guest_id', 'guests.id')
                    ->where('event_guest.event_id', $evento)
            ))
            ->ignore($this->route('guest'));

        return $rules;
    }

    public function messages(): array
    {
        return GuestDTO::messages();
    }
}
