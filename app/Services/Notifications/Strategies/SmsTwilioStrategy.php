<?php
namespace App\Services\Notifications\Strategies;

use App\Contracts\Notification\NotificationStrategyInterface;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class SmsTwilioStrategy implements NotificationStrategyInterface
{

    protected Client $twilio;
    protected string $fromNumber;

    public function __construct(Client $twilio)
    {
        $this->twilio = $twilio;
        $this->fromNumber = config('services.twilio.whatsapp_from');
    }

    public function send(string $to, string $message, array $payload = []): bool
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
}