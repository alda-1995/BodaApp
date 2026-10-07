<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pantallas que sólo tienen sentido con una invitación comprada y vigente
 * (invitados, notificaciones y configuración). Sin ella se manda al organizador
 * a "Información del evento", donde se le invita a comprar una.
 */
class EnsureActiveEvent
{
    public function handle(Request $request, Closure $next): Response
    {
        $event = $request->user()?->currentEvent();

        if (!$event?->isAvailable()) {
            return redirect()->route('events.info')->with(
                'error',
                // "Ya no está activa" y no "venció": también llega aquí la que el
                // superadmin apagó, con su fecha todavía por delante.
                $event
                    ? 'Tu invitación digital ya no está activa. Compra una nueva para volver a usar esta sección.'
                    : 'Necesitas una invitación digital para usar esta sección.'
            );
        }

        return $next($request);
    }
}
