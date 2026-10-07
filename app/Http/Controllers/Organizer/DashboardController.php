<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Services\EventWizardService;
use App\Services\Organizer\DashboardMetrics;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected EventWizardService $wizardService,
        protected DashboardMetrics $metrics,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        // Sólo su propia invitación; las compartidas están en su propio menú.
        $event = $user->currentEvent();

        if (!$event?->isAvailable()) {
            return view('organizer.dashboard.index', [
                'available' => false,
                'pastEvents' => $user->pastEvents(),
                'hasSharedEvents' => $user->coadminships()->accepted()->exists(),
            ]);
        }

        $progress = $this->wizardService->getProgress($event);

        return view('organizer.dashboard.index', [
            'available' => true,
            'event' => $event,
            'hasFeatures' => $event->hasFeatures(),
            'eventSlug' => $event->slug,
            'progress' => $progress,
            'progressUrl' => route('events.wizard.edit', array_filter([
                'event' => $event->slug,
                'step' => $progress['next_step'],
            ])),
            'metrics' => $this->metrics->for($event),
        ]);
    }
}
