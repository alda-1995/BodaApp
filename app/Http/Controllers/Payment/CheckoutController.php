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
            $token = $this->authService->generateOnboardingRedirectUrl($result['email']);

            if ($token) {
                $response['redirect_url'] = route('onboarding.password.view', [
                    'token' => $token,
                    'email' => $result['email'],
                ]);
            }
        }

        return response()->json($response);
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
