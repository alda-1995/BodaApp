<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Invitaciones de otras personas donde el usuario es coadministrador.
 */
class SharedEventsController extends Controller
{
    public function __construct(protected EventService $eventService)
    {
    }

    public function index(Request $request): View
    {
        return view('organizer.shared-events.index', [
            'events' => $this->eventService->sharedEventsFor($request->user()),
        ]);
    }
}
