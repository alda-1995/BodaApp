<?php

namespace App\View\Composers\Menu;

use App\Models\EventCoadmin;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SidebarComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $user = Auth::user();
        $hasSharedEvents = $user && $user->coadminships()->accepted()->exists();
        // Sin invitación vigente no hay a quién invitar ni qué configurar.
        $hasCurrentEvent = (bool) $user?->currentEvent()?->isAvailable();

        // Lleva a su propia invitación, o a la sugerencia de compra si no tiene una vigente.
        $eventItem = [
            'route' => 'events.info',
            'active' => ['events.wizard.*'],
            'icon' => 'document',
            'label' => 'Información del Evento',
        ];

        $sharedItem = $hasSharedEvents
            ? ['route' => 'shared-events.index', 'icon' => 'user', 'label' => 'Invitaciones compartidas']
            : null;

        $menuItems = [
            'superadmin' => [
                ['route' => 'superadmin.dashboard', 'icon' => 'house', 'label' => 'Panel'],
                ['route' => 'templates.index', 'icon' => 'document', 'label' => 'Plantillas'],
                ['route' => 'color-palettes.index', 'icon' => 'document', 'label' => 'Paletas de color'],
                ['route' => 'gift-registry.edit', 'icon' => 'document', 'label' => 'Tipos de mesa de regalo'],
                ['route' => 'admin.users.index', 'icon' => 'document', 'label' => 'Usuarios'],
                ['route' => 'superadmin.events.assets.index', 'active' => ['superadmin.events.assets.*'], 'icon' => 'edit', 'label' => 'Personalización'],
            ],
            'organizer' => array_filter([
                ['route' => 'panel', 'icon' => 'house', 'label' => 'Dashboard'],
                $eventItem,
                $hasCurrentEvent ? ['route' => 'organizer.guests.index', 'icon' => 'people', 'label' => 'Invitados'] : null,
                $hasCurrentEvent ? ['route' => 'organizer.notifications.index', 'icon' => 'plane', 'label' => 'Notificaciones'] : null,
                $hasCurrentEvent ? ['route' => 'organizer.rsvps.index', 'icon' => 'document-check', 'label' => 'Confirmaciones'] : null,
                $hasCurrentEvent ? ['route' => 'organizer.settings.index', 'icon' => 'settings', 'label' => 'Configuración'] : null,
                $sharedItem,
            ]),
        ];

        // El menú se arma por cada rol: un organizador que además es coadministrador
        // ya ve todo en el suyo y no debe ver las entradas repetidas.
        if ($user && !$user->hasRole('organizer')) {
            $menuItems[EventCoadmin::ROLE] = [
                ['route' => 'panel', 'icon' => 'house', 'label' => 'Dashboard'],
                $eventItem,
                ['route' => 'shared-events.index', 'icon' => 'user', 'label' => 'Invitaciones compartidas'],
            ];
        }

        $view->with('menuItems', $menuItems);
    }
}
