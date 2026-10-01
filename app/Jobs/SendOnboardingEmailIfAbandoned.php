<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Notifications\NotificationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Throwable;

class SendOnboardingEmailIfAbandoned implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Máximo número de intentos permitidos.
     */
    public int $tries = 3;

    /**
     * Backoff exponencial: reintenta a los 10s, 60s y 300s (5 min).
     */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public User $user
    ) {}

    public function handle(NotificationContext $notificationContext): void
    {
        $this->user->refresh();

        if (!is_null($this->user->password_changed_at)) {
            Log::info("El usuario {$this->user->email} ya definió su contraseña. Se omite el correo de abandono.");
            return;
        }

        $token = Password::createToken($this->user);

        $url = route('onboarding.password.view', [
            'token' => $token,
            'email' => $this->user->email,
        ]);

        $emailDriver = config('services.notifications.default_emails', 'resend');

        $mailable = (new MailMessage)
            ->subject('Completa la configuración de tu contraseña')
            ->greeting("¡Hola, {$this->user->name}!")
            ->line('Notamos que no completaste la configuración de tu contraseña tras tu compra.')
            ->line('Para configurar tu cuenta y acceder a la plataforma, ingresa en el siguiente enlace:')
            ->action('Configurar Contraseña', $url)
            ->line('Este enlace vencerá en 60 minutos.')
            ->line('Si ya configuraste tu contraseña, puedes ignorar este mensaje.');

        $htmlContent = $mailable->render()->toHtml();

        $sent = $notificationContext
            ->via($emailDriver)
            ->notify(
                recipient: $this->user->email,
                message: "Completa tu registro. Configura tu contraseña en: {$url}",
                payload: [
                    'subject' => 'Completa la configuración de tu contraseña - Tamira',
                    'html' => $htmlContent,
                ]
            );

        if (!$sent) {
            throw new RuntimeException("Fallo el envío de notificación por abandono vía {$emailDriver} para {$this->user->email}");
        }

        Log::info("Notificación por abandono enviada exitosamente a {$this->user->email}");
    }

    public function failed(Throwable $exception): void
    {
        Log::critical("Imposible enviar el correo de rescate tras {$this->tries} intentos.", [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'error' => $exception->getMessage(),
        ]);
    }
}