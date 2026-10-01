<?php

namespace App\Services\Notifications\Strategies;

use App\Contracts\Notification\NotificationStrategyInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SmtpMailStrategy implements NotificationStrategyInterface
{
    public function send(string $to, string $message, array $payload = []): bool
    {
        try {
            $fromAddress = $payload['from_address'] ?? config('mail.mailers.custom_smtp.username');

            $fromName = $payload['from_name'] ?? config('app.name');
            $html = $payload['html'] ?? null;

            Mail::mailer('custom_smtp')->send([], [], function ($mail) use ($to, $payload, $message, $html, $fromAddress, $fromName) {
                $mail->to($to)
                    ->subject($payload['subject'] ?? 'Notificación')
                    ->from($fromAddress, $fromName);

                if ($html) {
                    $mail->html((string) $html);
                } else {
                    $mail->text($message);
                }

                if (isset($payload['attachments']) && is_array($payload['attachments'])) {
                    foreach ($payload['attachments'] as $filePath) {
                        $mail->attach($filePath);
                    }
                }
            });

            return true;
        } catch (Throwable $e) {
            Log::error("Error inesperado enviando correo por SMTP a {$to}: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}