<?php

namespace App\Http\Controllers\Payment;

use App\DTOs\Order\CreateCheckoutSessionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\ProcessIdentityRequest;
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\TemplateService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected TemplateService $templateService,
        protected AuthService $authService
    ) {
    }

    public function showDetailPreview(string $slug): View
    {
        $template = $this->templateService->findBySlug($slug);
        if (!$template) {
            abort(404, 'Plantilla no encontrada');
        }
        return view('checkout.preview', compact('template'));
    }

    public function showDetailCheckout(string $slug): View
    {
        $template = $this->templateService->findBySlug($slug);
        if (!$template) {
            abort(404, 'Plantilla no encontrada');
        }
        return view('checkout.gateway', compact('template'));
    }

    public function success(Request $request): View|RedirectResponse
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('templates.index')
                ->with('error', 'Identificador de sesión inválido.');
        }

        return view('checkout.success', compact('sessionId'));
    }

    public function cancel(): View
    {
        return view('checkout.cancel');
    }

    public function checkStatus(string $sessionId): JsonResponse
    {
        $result = $this->orderService->getOrderStatusBySession($sessionId);
        $status = $result['status'] ?? 'pending';

        $response = [
            'status' => $status,
            'message' => $result['message'] ?? '',
        ];

        if ($status === 'completed' && !empty($result['email'])) {
            $response += $this->destinoTrasLaCompra($result['email']);
        }

        return response()->json($response);
    }

    /**
     * A dónde se manda al comprador en cuanto la orden queda pagada.
     *
     * Sólo pasa por el onboarding quien todavía no tiene contraseña propia: la
     * compra le creó la cuenta con una generada al azar. Quien ya se registró
     * antes —compró otra vez, o entró como coadministrador— ya eligió la suya,
     * así que no hay nada que crear ni que confirmar.
     *
     * @return array{redirect_url?: string, needs_password: bool}
     */
    private function destinoTrasLaCompra(string $email): array
    {
        $user = $this->authService->getUserWithEmail($email);

        if (!$user) {
            return ['needs_password' => false];
        }

        if (is_null($user->password_changed_at)) {
            $token = $this->authService->generateOnboardingRedirectUrl($email);

            if ($token) {
                return [
                    'redirect_url' => route('onboarding.password.view', [
                        'token' => $token,
                        'email' => $email,
                    ]),
                    'needs_password' => true,
                ];
            }
        }

        // Si ya venía con la sesión abierta no se le pide entrar otra vez.
        $yaDentro = Auth::check() && Auth::user()->email === $email;

        return [
            'redirect_url' => $yaDentro ? route('panel') : route('login', ['email' => $email]),
            'needs_password' => false,
        ];
    }

    public function processIdentity(ProcessIdentityRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $dto = new CreateCheckoutSessionDTO(
                templateId: (int) $data['template_id'],
                customerEmail: $data['email'],
            );
            $resultCheckoutUrl = $this->orderService->createStripeCheckoutSession($dto);
            return redirect()->away($resultCheckoutUrl);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
