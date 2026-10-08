<?php
use App\Http\Controllers\Admin\ColorPaletteController;
use App\Http\Controllers\Admin\GiftRegistryConfigController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Event\CoadminInvitationController;
use App\Http\Controllers\Event\InvitationController;
use App\Http\Controllers\Event\RsvpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Payment\CheckoutController;
use App\Http\Controllers\Payment\OnboardingController;
use App\Http\Controllers\Payment\StripeWebhookController;
use App\Http\Controllers\Template\TemplatePreviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get("/", [HomeController::class, "index"])->name("home");

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

    Route::get('/forgot-password', [AuthController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.update');

    Route::get('/onboarding/set-password', [OnboardingController::class, 'showSetPasswordView'])
        ->name('onboarding.password.view');
    Route::post('/onboarding/set-password', [OnboardingController::class, 'storePassword'])
        ->name('onboarding.password.store');
});

/*
 * Dirección de la invitación, el paso que cierra la compra.
 *
 * Fuera de 'guest' a propósito: por aquí pasa tanto quien acaba de crear su
 * cuenta —todavía sin sesión— como quien ya la tenía y compró otra invitación
 * con la sesión abierta. Quién es cada quien no lo decide este grupo: la vista
 * exige un enlace firmado y el guardado lee el correo de la sesión.
 */
Route::get('/onboarding/setup-profile', [OnboardingController::class, 'showSetupProfileView'])
    ->name('onboarding.profile.view');

Route::post('/onboarding/setup-profile', [OnboardingController::class, 'storeProfile'])
    ->name('onboarding.profile.store');

// Vista previa de la URL amigable mientras se escriben los nombres de la pareja.
Route::get('/onboarding/url-preview', [OnboardingController::class, 'previewUrl'])
    ->middleware('throttle:60,1')
    ->name('onboarding.url.preview');

Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/templates/preview/{templateName}', TemplatePreviewController::class)->name('templates.preview');

// Invitación de coadministrador (liga firmada que llega por correo).
Route::get('/coadministrador/invitacion/{invitation}', [CoadminInvitationController::class, 'show'])
    ->whereNumber('invitation')
    ->name('coadmin.invitation.show');
Route::post('/coadministrador/invitacion/{invitation}', [CoadminInvitationController::class, 'register'])
    ->whereNumber('invitation')
    ->middleware('throttle:10,1')
    ->name('coadmin.invitation.register');

// Invitación digital pública; hoy redirige URLs anteriores a la actual.
Route::get('/invitacion/{slug}/{guest?}', [InvitationController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('invitation.show');

// Confirmación de asistencia desde la propia invitación (responde JSON).
Route::post('/invitacion/{slug}/confirmar', [RsvpController::class, 'store'])
    ->where('slug', '[a-z0-9\-]+')
    ->middleware('throttle:10,1')
    ->name('invitation.rsvp');

Route::middleware(['auth'])->group(function () {
    // Route::get('/orders/user/{user_id}', [CheckoutController::class, 'index'])->name('orders.index');

    // Route::get('/orders/detail/{order_id}', [CheckoutController::class, 'show'])->name('orders.show');
});

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('checkout-preview/{slug}', [CheckoutController::class, 'showDetailPreview'])->name('checkout-preview')->where('slug', '[a-zA-Z0-9\-]+');
    Route::get('identify/{template_id}', [CheckoutController::class, 'showDetailCheckout'])->name('detail-payment');
    Route::post('/process-identity', [CheckoutController::class, 'processIdentity'])->name('process-identity');
    Route::get('/payment/success', [CheckoutController::class, 'success'])->name('success');
    Route::get('/payment/cancel', [CheckoutController::class, 'cancel'])->name('cancel');

    Route::get('/status/{sessionId}', [CheckoutController::class, 'checkStatus'])->name('status');
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');


Route::middleware(['auth'])->group(function () {
    Route::get('/color-palettes', [ColorPaletteController::class, 'index'])->name('color-palettes.index');
    Route::post('/color-palettes', [ColorPaletteController::class, 'store'])->name('color-palettes.store');
    Route::put('/color-palettes/{id}', [ColorPaletteController::class, 'update'])->name('color-palettes.update');
    Route::delete('/color-palettes/{id}', [ColorPaletteController::class, 'destroy'])->name('color-palettes.destroy');

    Route::get('users', [UserController::class, 'index'])->name('admin.users.index');

    Route::get('gift-registry-config', [GiftRegistryConfigController::class, 'edit'])->name('gift-registry.edit');
    Route::put('gift-registry-config', [GiftRegistryConfigController::class, 'update'])->name('gift-registry.update');
});


// Route::post('/organizer/notifications/send', [NotificationController::class, 'sendNotifications'])
//     ->middleware(['auth'])
//     ->name('organizer.notifications.send');

