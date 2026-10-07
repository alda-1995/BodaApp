<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\UpdateEventValidityRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Vigencia de una invitación, vista por el superadmin.
 *
 * Normalmente la vigencia se calcula sola: fecha de la boda más los días de la
 * plantilla (Event::saving). Esta pantalla es para los casos en que hay que
 * intervenir a mano —una pareja que pospuso la boda, un regalo de cortesía, una
 * invitación que hay que cerrar antes de tiempo— y para probar el vencimiento
 * sin tener que tocar la base.
 *
 * Son dos cosas distintas y por eso se editan juntas:
 *  - 'expires_at' dice hasta cuándo sirve.
 *  - 'is_active' es el interruptor; el comando por horas lo apaga al vencer.
 * Extender la fecha de una ya apagada no la revive: hay que volver a encenderla.
 */
class EventValidityController extends Controller
{
    public function edit(Event $event): View
    {
        return view('admin.event-validity.edit', [
            'event' => $event->load('template', 'user'),
        ]);
    }

    public function update(UpdateEventValidityRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();
        $antes = [
            'expires_at' => $event->expires_at?->toDateTimeString(),
            'is_active' => $event->is_active,
        ];

        /*
         * forceFill y no update(): 'expires_at' se recalcula solo cuando cambia
         * la fecha de la boda, y aquí se está fijando a mano justamente para
         * que mande sobre ese cálculo.
         */
        $event->forceFill([
            'expires_at' => $data['expires_at'] ?: null,
            'is_active' => $data['is_active'],
        ])->save();

        // Tocar la vigencia de una boda ajena deja rastro.
        Log::info('Superadmin cambió la vigencia de una invitación', [
            'event_id' => $event->id,
            'superadmin_id' => $request->user()->id,
            'antes' => $antes,
            'despues' => [
                'expires_at' => $event->expires_at?->toDateTimeString(),
                'is_active' => $event->is_active,
            ],
            'motivo' => $data['reason'] ?? null,
        ]);

        return redirect()
            ->route('superadmin.events.validity.edit', $event->id)
            ->with('success', 'Vigencia actualizada.');
    }
}
