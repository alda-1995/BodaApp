<?php

namespace App\Http\Controllers\Organizer;

use App\Exports\ConfirmedRsvpsExport;
use App\Http\Controllers\Controller;
use App\Services\GuestService;
use App\Services\Invitation\RsvpReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Confirmación de asistencia vista por el organizador: quién respondió, cuánta
 * gente viene y a quién le falta contestar.
 */
class RsvpController extends Controller
{
    public function __construct(
        protected RsvpReportService $report,
        protected GuestService $guests,
    ) {
    }

    public function index(Request $request): View
    {
        $event = $this->guests->eventFor($request->user());
        abort_unless($event, 404);

        $tab = $request->query('status') === RsvpReportService::TAB_NOT_CONFIRMED
            ? RsvpReportService::TAB_NOT_CONFIRMED
            : RsvpReportService::TAB_CONFIRMED;

        $search = trim((string) $request->query('search', '')) ?: null;
        $invitations = $this->report->paginate($event, $tab, $search);

        return view('organizer.rsvps.index', [
            'event' => $event,
            'tab' => $tab,
            'search' => $search,
            'invitations' => $invitations,
            'stats' => $this->report->statsFor($event),
            'questions' => $this->report->questionLabels($event, $invitations),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $event = $this->guests->eventFor($request->user());
        abort_unless($event, 404);

        $name = 'confirmados-' . $event->custom_url . '.xlsx';

        return Excel::download(new ConfirmedRsvpsExport($this->report->confirmed($event)), $name);
    }
}
