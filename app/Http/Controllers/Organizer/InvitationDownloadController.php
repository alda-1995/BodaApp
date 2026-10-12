<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Invitation\StaticInvitationExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * La copia de la invitación que el organizador se lleva cuando ya venció.
 *
 * Sólo se descarga lo que ya no está en pie: mientras la invitación sirve, su
 * dirección es la copia buena y no hay nada que guardar.
 */
class InvitationDownloadController extends Controller
{
    public function __construct(private readonly StaticInvitationExport $export)
    {
    }

    public function __invoke(Request $request, Event $event): BinaryFileResponse
    {
        abort_unless($event->user_id === $request->user()->id, 403);

        // Una invitación en pie no se descarga: para eso está su dirección.
        abort_if($event->isAvailable(), 404);

        try {
            $zip = $this->export->zipFor($event);
        } catch (Throwable $e) {
            report($e);

            abort(503, 'No pudimos preparar tu invitación para descargar. Inténtalo de nuevo en un momento.');
        }

        return response()
            ->download($zip, $this->export->folderName($event) . '.zip')
            ->deleteFileAfterSend();
    }
}
