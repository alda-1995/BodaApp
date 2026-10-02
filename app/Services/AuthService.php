<?php
namespace App\Services;

use App\DTOs\Auth\ForgotPasswordDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use App\DTOs\Auth\UpdateUserDTO;
use App\Events\UserRegistered;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifications\NotificationContext;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Notifications\Messages\MailMessage;

class AuthService
{
    public function __construct(
        protected NotificationContext $notificationContext
    ) {
    }

    public function attemptLogin(LoginDTO $dto): bool
    {
        return Auth::attempt([
            'email' => $dto->email,
            'password' => $dto->password,
        ]);
    }

    public function register(RegisterDTO $dto, string $rolName = 'organizer'): ?User
    {
        try {
            return DB::transaction(function () use ($dto, $rolName) {
                $user = User::create([
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'password' => Hash::make($dto->password),
                ]);

                $role = Role::where('name', $rolName)->first();
                if ($role) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }

                return $user;
            });
        } catch (Exception $e) {
            Log::error('Error crítico al registrar usuario: ' . $e->getMessage(), [
                'data' => $dto,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    public function update(int $userId, UpdateUserDTO $dto): ?User
    {
        try {
            $user = User::findOrFail($userId);

            $user->update([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password ? Hash::make($dto->password) : $user->password,
            ]);

            return $user;
        } catch (Exception $e) {
            Log::error('Error al actualizar usuario: ' . $e->getMessage(), [
                'user_id' => $userId,
                'data' => $dto,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function sendResetLink(ForgotPasswordDTO $dto): string
    {
        $user = $this->getUserWithEmail($dto->email);

        if (!$user) {
            return Password::INVALID_USER;
        }

        $token = Password::createToken($user);

        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        $emailDriver = config('services.notifications.default_emails', 'resend');

        $mailable = (new MailMessage)
            ->subject('Restablecer contraseña')
            ->greeting("¡Hola, {$user->name}!")
            ->line('Recibiste este correo porque solicitaste un restablecimiento de contraseña para tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line('Este enlace para restablecer la contraseña caducará en 60 minutos.')
            ->line('Si no solicitaste un restablecimiento de contraseña, no se requiere ninguna otra acción.');

        $htmlContent = $mailable->render()->toHtml();

        $sent = $this->notificationContext
            ->via($emailDriver)
            ->notify(
                recipient: $user->email,
                message: "Solicitaste restablecer tu contraseña: {$url}",
                payload: [
                    'subject' => 'Restablecer contraseña',
                    'html' => $htmlContent,
                ]
            );

        if (!$sent) {
            Log::error("Fallo al enviar el correo de recuperación a: {$user->email} usando el driver {$emailDriver}");
            return 'passwords.sent_failed';
        }

        return Password::RESET_LINK_SENT;
    }

    public function resetPassword(ResetPasswordDTO $dto): string
    {
        $emailDriver = config('services.notifications.default_emails', 'resend');

        $notificationSent = true;

        $status = Password::reset(
            [
                'token' => $dto->token,
                'email' => $dto->email,
                'password' => $dto->password,
                'password_confirmation' => $dto->password_confirmation,
            ],
            function ($user, $password) use ($emailDriver, &$notificationSent) {
                $user->password = Hash::make($password);
                // La persona acaba de elegir su contraseña: deja de necesitar el
                // onboarding de la compra (ver CheckoutController::checkStatus).
                $user->password_changed_at = now();
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new \Illuminate\Auth\Events\PasswordReset($user));

                $mailable = (new MailMessage)
                    ->subject('Contraseña restablecida con éxito')
                    ->greeting("¡Hola, {$user->name}!")
                    ->line('Te confirmamos que la contraseña de tu cuenta ha sido modificada correctamente.')
                    ->line('Si no realizaste este cambio, por favor contacta con soporte inmediatamente.');

                $htmlContent = $mailable->render()->toHtml();

                $notificationSent = $this->notificationContext
                    ->via($emailDriver)
                    ->notify(
                        recipient: $user->email,
                        message: 'Tu contraseña ha sido restablecida correctamente.',
                        payload: [
                            'subject' => 'Contraseña restablecida con éxito',
                            'html' => $htmlContent,
                        ]
                    );
            }
        );
        
        if ($status === Password::PASSWORD_RESET && !$notificationSent) {
            Log::error("Se restableció la contraseña pero falló el envío de confirmación a: {$dto->email} con el driver {$emailDriver}");
        }

        return $status;
    }

    public function getUserWithEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function generateOnboardingRedirectUrl(string $email): ?string
    {
        $user = $this->getUserWithEmail($email);

        if (!$user) {
            return null;
        }

        return Password::createToken($user);
    }

    public function logout(): void
    {
        Auth::logout();
    }
}