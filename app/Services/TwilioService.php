<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioService
{
    protected Client $twilio;
    protected string $fromNumber;

    public function __construct(Client $twilio)
    {
        $this->twilio = $twilio;
        $this->fromNumber = config('services.twilio.whatsapp_from');
    }

    public function sendSms($to, $message): bool
    {
        try {
            $this->twilio->messages->create($to, [
                'from' => config('services.twilio.sms_from'),
                'body' => $message,
            ]);
            return true;
        } catch (TwilioException $e) {
            Log::error("Error enviando SMS a {$to}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Error inesperado enviando SMS a {$to}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            return false;
        }
    }

    public function sendWhatsapp($to, $name, $slug): bool
    {
        try {
            $url = route('home', ['guest' => $slug]);
            $this->twilio->messages->create("whatsapp:$to", [
                'messagingServiceSid' => config('services.twilio.services_id'),
                'contentSid' => config('services.twilio.whatsapp_template_id'),
                'contentVariables' => json_encode([
                    '1' => $name,
                    '2' => $url
                ]),
                'from' => $this->fromNumber
            ]);
            return true;
        } catch (TwilioException $e) {
            Log::error("Error enviando WhatsApp a {$to}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Error inesperado enviando WhatsApp a {$to}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            return false;
        }
    }
}