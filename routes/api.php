<?php
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Twilio\TwilioWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/twilio/whatsapp-webhook', [TwilioWebhookController::class, 'handle'])
    ->name('twilio.whatsapp.webhook');

Route::post('/checkout/{template}', PaymentController::class);