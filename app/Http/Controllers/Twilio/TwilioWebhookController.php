<?php

namespace App\Http\Controllers\Twilio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Twilio\TwilioWebhookRequest;
use App\Services\WhatsappMessageService;
use Illuminate\View\View;
use Twilio\TwiML\MessagingResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class TwilioWebhookController extends Controller
{
    public function __construct(protected WhatsappMessageService $whatsappMessageService)
    {

    }

    public function handle(TwilioWebhookRequest $request)
    {
        $dataToExtract = $request->only([
            'From',
            'To',
            'WaId',
            'ProfileName',
            'Body',
            'MessageSid'
        ]);

        $dataToSave = [
            'from_number' => str_replace('whatsapp:', '', $dataToExtract['From']),
            'to_number' => str_replace('whatsapp:', '', $dataToExtract['To']),
            'wa_id' => $dataToExtract['WaId'] ?? null,
            'profile_name' => $dataToExtract['ProfileName'] ?? null,
            'body' => $dataToExtract['Body'] ?? null,
            'message_sid' => $dataToExtract['MessageSid'],
        ];
        $message = $this->whatsappMessageService->createMessage($dataToSave);

        $response = new MessagingResponse();

        if ($message) {
            $response->message("Gracias por tu mensaje. Este número es solo para el envío de invitaciones, por lo que no podemos recibir ni registrar respuestas por aquí. Para confirmar tu asistencia, por favor ingresa al enlace que te enviamos en el mensaje anterior y completa el proceso directamente en la invitación. ¡Gracias por tu apoyo y comprensión!");
        } else {
            $response->message("Este número es solo para el envío de invitaciones, por lo que no podemos recibir ni registrar respuestas por aquí. Para confirmar tu asistencia, por favor ingresa al enlace que te enviamos en el mensaje anterior y completa el proceso directamente en la invitación. ¡Gracias por tu apoyo y comprensión!");
            Log::warning('Twilio Webhook: Mensaje validado pero falló el servicio de guardado.', ['data' => $dataToSave]);
        }
        return response((string) $response, Response::HTTP_OK)
            ->header('Content-Type', 'text/xml');
    }
}
