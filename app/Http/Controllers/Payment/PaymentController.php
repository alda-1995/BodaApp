<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\DTOs\Payment\StripeCheckoutDTO;
use App\Models\Template;
use App\Services\Payment\CheckoutProcessorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected CheckoutProcessorService $checkoutProcessor
    ) {}

    public function __invoke(Request $request, Template $template): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:stripe,paypal'
        ]);

        $provider = $request->input('provider');
        $user = $request->user();

        $dto = match ($provider) {
            'stripe' => new StripeCheckoutDTO(
                userId: $user->id,
                customerEmail: $user->email,
                templateId: $template->id,
                stripePriceId: $template->stripe_price_id,
                amount: (float) $template->price
            ),
        };

        try {
            $response = $this->checkoutProcessor->execute($dto, $provider);
            
            return response()->json(['redirect_url' => $response->redirectUrl], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
