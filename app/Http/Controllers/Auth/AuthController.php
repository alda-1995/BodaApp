<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\Auth\ForgotPasswordDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Event\CoadminInvitationController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\EventCoadmin;
use App\Providers\RouteServiceProvider;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $dto = LoginDTO::fromRequest($request);

        if ($this->authService->attemptLogin($dto)) {
            $request->session()->regenerate();

            $user = auth()->user();

            // Inició sesión para aceptar una invitación de coadministrador.
            if ($invitationUrl = $request->session()->pull(CoadminInvitationController::SESSION_RETURN_URL)) {
                return redirect()->to($invitationUrl);
            }

            if ($user->hasAnyRole(['superadmin', 'organizer', EventCoadmin::ROLE])) {
                return redirect()->to($user->homeUrl());
            }

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return back()->withErrors(['email' => 'Las credenciales no son válidas.']);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function logout(Request $request)
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(ForgotPasswordRequest $request)
    {
        $dto = new ForgotPasswordDTO($request->validated());
        $status = $this->authService->sendResetLink($dto);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        if ($status === 'passwords.sent_failed') {
            return back()->withErrors([
                'email' => 'No se pudo enviar el correo de recuperación. Por favor, reintenta más tarde o contacta al soporte.'
            ]);
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(Request $request, $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $dto = new ResetPasswordDTO($request->validated());
        $status = $this->authService->resetPassword($dto);

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }
        
        return back()->withErrors(['email' => [__($status)]]);
    }
}
