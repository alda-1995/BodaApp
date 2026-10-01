<?php

namespace App\Http\Controllers\Organizer;

use App\DTOs\Guest\GuestDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\UpdateGuestRequest;
use App\Services\GuestService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestController extends Controller
{
    public function __construct(protected GuestService $guestService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', '')) ?: null;

        $guests = $this->guestService->paginateForUser($user, $search);
        $event = $this->guestService->eventFor($user);

        return view('organizer.guests.index', compact('guests', 'event', 'search'));
    }

    public function create(Request $request): View
    {
        $event = $this->guestService->eventFor($request->user());

        return view('organizer.guests.create', compact('event'));
    }

    public function store(UpdateGuestRequest $request): RedirectResponse
    {
        try {
            $this->guestService->createForUser($request->user(), GuestDTO::fromArray($request->validated()));
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('organizer.guests.index')->with('success', 'Invitado creado correctamente.');
    }

    public function show(Request $request, int $guest): View
    {
        $user = $request->user();
        $guest = $this->guestService->findForUser($user, $guest);
        $invitation = $guest->invitationFor($this->guestService->eventFor($user));

        return view('organizer.guests.show', compact('guest', 'invitation'));
    }

    public function edit(Request $request, int $guest): View
    {
        $user = $request->user();
        $guest = $this->guestService->findForUser($user, $guest);
        $invitation = $guest->invitationFor($this->guestService->eventFor($user));

        return view('organizer.guests.edit', compact('guest', 'invitation'));
    }

    public function update(UpdateGuestRequest $request, int $guest): RedirectResponse
    {
        $user = $request->user();
        $guest = $this->guestService->findForUser($user, $guest);

        try {
            $this->guestService->updateForUser($user, $guest, GuestDTO::fromArray($request->validated()));
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('organizer.guests.show', $guest->id)->with('success', 'Invitado actualizado correctamente.');
    }

    public function destroy(Request $request, int $guest): RedirectResponse
    {
        $guest = $this->guestService->findForUser($request->user(), $guest);

        try {
            $this->guestService->deleteGuest($guest);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('organizer.guests.index')->with('success', 'Invitado eliminado correctamente.');
    }

    public function import(): View
    {
        return view('organizer.guests.import');
    }

    public function processImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Selecciona el archivo con tu lista de invitados.',
            'file.mimes' => 'El archivo debe ser CSV. En Excel o Google Sheets: Archivo › Descargar › CSV.',
            'file.max' => 'El archivo no debe pesar más de 5 MB.',
        ]);

        $result = $this->guestService->importCsvForUser($request->user(), $request->file('file'));

        if ($result['created'] === 0) {
            return back()
                ->with('error', 'No se importó ningún invitado.')
                ->with('import_errors', $result['errors']);
        }

        return redirect()->route('organizer.guests.index')
            ->with('success', "Se importaron {$result['created']} invitados.")
            ->with('import_errors', $result['errors']);
    }

    /**
     * Plantilla vacía para importar: sólo la fila de encabezados, un campo por
     * columna y sin datos de ejemplo.
     *
     * Separada por comas: es lo que Excel en México y Google Sheets dividen en
     * columnas al abrir el archivo (con ';' todo quedaba en la columna A).
     */
    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            // BOM para que Excel abra los acentos correctamente.
            echo "\xEF\xBB\xBF";
            echo "Nombre,Teléfono,Correo,Acompañantes\n";
        }, 'formato_invitados.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
