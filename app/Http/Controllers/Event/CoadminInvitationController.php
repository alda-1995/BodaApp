<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\EventCoadmin;
use App\Models\User;
use App\Services\CoadminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Liga que recibe el coadministrador por correo. Va firmada y caduca: sin firma
 * válida no se acepta nada.
 */
class CoadminInvitationController extends Controller
{
    /** Sesión: a dónde volver tras iniciar sesión para aceptar. */
    public const SESSION_RETURN_URL = 'coadmin_invitation_url';

    public function __construct(protected CoadminService $coadminService)
    {
    }

    public function show(Request $request, int $invitation): View|RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            return $this->state('expired');
        }

        $coadmin = EventCoadmin::with('event')->find($invitation);

        if (!$coadmin) {
            return $this->state('unavailable'); // el dueño quitó la invitación
        }

        $user = $request->user();

        if ($coadmin->isAccepted()) {
            return $user
                ? redirect()->route('panel')
                : redirect()->route('login')->with('success', 'Ya aceptaste esta invitación. Inicia sesión para entrar.');
        }

        if ($user) {
            if (mb_strtolower($user->email) !== $coadmin->email) {
                return $this->state('wrong_account', ['invitation' => $coadmin]);
            }

            $this->coadminService->accept($coadmin, $user);

            return redirect()->route('panel')->with('success', 'Ya puedes ayudar a administrar la boda.');
        }

        if (User::where('email', $coadmin->email)->exists()) {
            $request->session()->put(self::SESSION_RETURN_URL, $request->fullUrl());

            return redirect()->route('login')
                ->with('success', "Inicia sesión con {$coadmin->email} para aceptar la invitación.");
        }

        return $this->state('register', ['invitation' => $coadmin]);
    }

    /**
     * Crea la cuenta de quien aún no tiene una y acepta la invitación.
     */
    public function register(Request $request, int $invitation): View|RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            return $this->state('expired');
        }

        $coadmin = EventCoadmin::find($invitation);

        // El correo sale de la invitación; si ya existe esa cuenta, debe iniciar sesión.
        if (!$coadmin || $coadmin->isAccepted() || User::where('email', $coadmin->email)->exists()) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'name.required' => 'Escribe tu nombre.',
            'password.required' => 'Crea una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = $this->coadminService->registerAndAccept($coadmin, $data['name'], $data['password']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('panel')->with('success', 'Ya puedes ayudar a administrar la boda.');
    }

    private function state(string $state, array $data = []): View
    {
        return view('coadmin.invitation', ['state' => $state] + $data);
    }
}
