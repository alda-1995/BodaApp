<?php

namespace App\Http\Controllers\Payment;

use App\DTOs\Auth\UpdateUserDTO;
use App\DTOs\Onboarding\OnboardingProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\OnboardingSetPasswordRequest;
use App\Http\Requests\Onboarding\OnboardingStepTwoRequest;
use App\Models\Event;
use App\Services\AuthService;
use App\Services\EventService;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Support\Facades\URL;
use Log;

class OnboardingController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected EventService $eventService
    ) {
    }

    public function showSetPasswordView(Request $request): View|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('login')
                ->with('error', 'Enlace de acceso inválido o incompleto.');
        }

        $email = $request->query('email');
        $token = $request->query('token');

        $user = $this->authService->getUserWithEmail($email);

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'El correo proporcionado no está registrado.');
        }

        if (!Password::tokenExists($user, $token)) {
            return redirect()->route('login')
                ->with('error', 'El enlace para crear tu contraseña ha expirado o es inválido.');
        }

        return view('checkout.onboarding.set-password', compact('email', 'token'));
    }

    public function storePassword(OnboardingSetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $this->authService->getUserWithEmail($validated['email']);

        if (!$user || !Password::tokenExists($user, $validated['token'])) {
            return redirect()->route('password.request', ['email' => $validated['email']])
                ->with('error', 'El enlace para configurar tu contraseña ha expirado o es inválido. Solicita uno nuevo.');
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ])->save();

        Password::deleteToken($user);

        return redirect()->to($this->authService->generateProfileSetupUrl($user->email));
    }

    public function showSetupProfileView(Request $request): View|RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('onboarding.password.view')
                ->with('error', 'El enlace de onboarding es inválido o ha expirado.');
        }

        $email = $request->query('email');
        $user = $email ? $this->authService->getUserWithEmail($email) : null;

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'No encontramos una cuenta asociada para continuar.');
        }

        /*
         * De quién es este paso queda en la sesión, no en el formulario.
         *
         * Al guardar se inicia sesión como esa persona, así que el correo no
         * puede venir de un campo que cualquiera pueda cambiar.
         */
        $request->session()->put('onboarding.email', $user->email);

        // Quien ya tenía cuenta no pasó por crear contraseña: para esa persona
        // este es el único paso, y decirle "2 de 2" sería mentirle.
        $pasoUnico = Auth::check() && Auth::id() === $user->id;

        return view('checkout.onboarding.setup-profile', compact('user', 'pasoUnico'));
    }

    /**
     * Vista previa de la URL amigable mientras se escriben los nombres de la pareja.
     * Devuelve exactamente la URL que se guardará, con el sufijo si ya existe.
     */
    public function previewUrl(Request $request): JsonResponse
    {
        $data = $request->validate([
            'partner_1_name' => ['nullable', 'string', 'max:100'],
            'partner_2_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email'],
        ]);

        $partner1 = trim((string) ($data['partner_1_name'] ?? ''));
        $partner2 = trim((string) ($data['partner_2_name'] ?? ''));

        if ($partner1 === '' || $partner2 === '') {
            return response()->json(['slug' => null, 'url' => null]);
        }

        // El propio evento no cuenta como duplicado de sí mismo.
        $user = !empty($data['email']) ? $this->authService->getUserWithEmail($data['email']) : null;
        $event = $user ? $this->eventService->findByUserId($user->id) : null;

        $slug = $this->eventService->uniqueCustomUrl($partner1, $partner2, $event);

        return response()->json([
            'slug' => $slug,
            'url' => Event::invitationUrlFor($slug),
        ]);
    }

    public function storeProfile(OnboardingStepTwoRequest $request): RedirectResponse
    {
        // El correo sale de la sesión que dejó el enlace firmado, nunca del
        // formulario: aquí abajo se inicia sesión como esa persona.
        $email = $request->session()->get('onboarding.email');
        $user = $email ? $this->authService->getUserWithEmail($email) : null;

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Tu sesión de configuración expiró. Vuelve a entrar para terminar.');
        }

        $dto = OnboardingProfileDTO::fromRequest($request);

        try {
            DB::transaction(function () use ($user, $dto) {
                $updateUserDTO = new UpdateUserDTO([
                    'name' => $dto->userName,
                    'email' => $user->email,
                    'password' => null,
                ]);

                $this->authService->update($user->id, $updateUserDTO);

                $eventFind = $this->eventService->findByUserId($user->id);
                if (!$eventFind) {
                    throw new \Exception("No se encontró ningún evento asociado al usuario ID {$user->id}.");
                }
                // Nombres cortos sólo para la dirección de la invitación: los del
                // evento (con apellidos) se capturan después en el wizard.
                $this->eventService->updateUrlNames($eventFind, $dto->partner1Name, $dto->partner2Name);
            });

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget('onboarding.email');

            return redirect()->route('panel')
                ->with('success', '¡Cuenta y evento configurados con éxito!');

        } catch (\Throwable $e) {
            Log::error('Error en OnboardingStoreProfile: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Ocurrió un error al guardar la información. Por favor, intenta de nuevo.');
        }
    }
}