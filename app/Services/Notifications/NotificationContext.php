<?php
namespace App\Services\Notifications;

use App\Contracts\Notification\NotificationStrategyInterface;
use App\Services\Notifications\Strategies\ResendMailStrategy;
use App\Services\Notifications\Strategies\SmsTwilioStrategy;
use App\Services\Notifications\Strategies\SmtpMailStrategy;
use InvalidArgumentException;

class NotificationContext
{
    protected NotificationStrategyInterface $strategy;

    protected array $drivers = [
        'smtp' => SmtpMailStrategy::class,
        'sms' => SmsTwilioStrategy::class,
        'resend' => ResendMailStrategy::class
    ];

    public function __construct(?NotificationStrategyInterface $strategy = null)
    {
        if ($strategy) {
            $this->strategy = $strategy;
        }
    }

    public function setStrategy(NotificationStrategyInterface $strategy): self
    {
        $this->strategy = $strategy;
        return $this;
    }

    public function via(string $driver): self
    {
        if (!isset($this->drivers[$driver])) {
            throw new InvalidArgumentException("El canal de notificación [{$driver}] no está soportado.");
        }

        $this->strategy = app($this->drivers[$driver]);
        return $this;
    }

    public function notify(string $recipient, string $message, array $payload = []): bool
    {
        if (!isset($this->strategy)) {
            throw new InvalidArgumentException("No se ha definido ninguna estrategia de notificación.");
        }

        return $this->strategy->send($recipient, $message, $payload);
    }
}