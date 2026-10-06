<?php
use App\Http\Controllers\Event\EventSetupController;
use App\Http\Controllers\Organizer\DashboardController;
use App\Http\Controllers\Organizer\EventInfoController;
use App\Http\Controllers\Organizer\SharedEventsController;
use App\Http\Controllers\Organizer\GuestController;
use App\Http\Controllers\Organizer\NotificationController;
use App\Http\Controllers\Organizer\OrderController;
use App\Http\Controllers\Organizer\RsvpController;
use App\Http\Controllers\Organizer\SettingsController;
use Illuminate\Support\Facades\Route;


// Dueño y coadministradores: panel e información del evento (EventPolicy decide
// sobre qué evento puede trabajar cada uno).
Route::middleware(['auth', 'role:organizer|coadmin'])
    ->group(function () {
        Route::get('/panel', [DashboardController::class, 'index'])->name('panel');
        Route::get('/informacion-evento', EventInfoController::class)->name('events.info');
        Route::get('/invitaciones-compartidas', [SharedEventsController::class, 'index'])->name('shared-events.index');
        Route::get('/configuracion-evento/{event:slug}/{step?}', [EventSetupController::class, 'edit'])
            ->name('events.wizard.edit');
        Route::put('/configuracion-evento/{event:slug}/{step}', [EventSetupController::class, 'update'])
            ->name('events.wizard.update');
    });

/*
 * Historial de pagos del dueño.
 *
 * Va fuera de 'event.active' a propósito: cuando una invitación vence es justo
 * cuando alguien quiere revisar qué pagó, y cerrarle su propio historial sería
 * lo contrario de lo que necesita.
 */
Route::middleware(['auth', 'role:organizer'])
    ->prefix('configuracion/pagos')
    ->name('organizer.orders.')
    ->controller(OrderController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{order}', 'show')->whereNumber('order')->name('show');
    });

// Sólo el dueño, y sólo con una invitación comprada y vigente.
Route::middleware(['auth', 'role:organizer', 'event.active'])
    ->group(function () {
        Route::prefix('configuracion')
            ->name('organizer.settings.')
            ->controller(SettingsController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::put('/', 'update')->name('update');
                Route::get('/vista-previa-url', 'urlPreview')->middleware('throttle:60,1')->name('url_preview');
                Route::post('/coadministradores', 'inviteCoadmin')->middleware('throttle:10,1')->name('coadmins.store');
                Route::delete('/coadministradores/{coadmin}', 'removeCoadmin')
                    ->whereNumber('coadmin')
                    ->name('coadmins.destroy');
            });

        // Invitados del organizador. Las rutas fijas (importar) van antes de /{guest}.
        Route::prefix('invitados')
            ->name('organizer.guests.')
            ->controller(GuestController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/crear', 'create')->name('create');
                Route::post('/', 'store')->name('store');

                Route::get('/importar', 'import')->name('import');
                Route::post('/importar', 'processImport')->name('process_import');
                Route::get('/importar/formato', 'importTemplate')->name('import_template');

                Route::get('/{guest}', 'show')->whereNumber('guest')->name('show');
                Route::get('/{guest}/editar', 'edit')->whereNumber('guest')->name('edit');
                Route::put('/{guest}', 'update')->whereNumber('guest')->name('update');
                Route::delete('/{guest}', 'destroy')->whereNumber('guest')->name('destroy');
            });

        // Confirmaciones que llegaron desde la invitación.
        Route::prefix('confirmaciones')
            ->name('organizer.rsvps.')
            ->controller(RsvpController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/exportar', 'export')->name('export');
            });

        Route::prefix('notificaciones')
            ->name('organizer.notifications.')
            ->controller(NotificationController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/enviar', 'send')->name('send');
            });
    });

// Route::prefix('organizer')
//     ->name('organizer.')
//     ->middleware(['auth', 'role:organizer'])
//     ->group(function () {


// Route::prefix('rsvps')->name('rsvps.')->group(function () {
//     Route::get('/', [RsvpController::class, 'index'])->name('index');
//     Route::post('/send-reminder', [RsvpController::class, 'sendReminder'])->name('sendReminder');
//     Route::get('/export', [RsvpController::class, 'exportConfirmed'])
//         ->name('export');
// });

// Route::prefix('notifications')->name('notifications.')->group(function () {
//     Route::get('/', [NotificationController::class, 'index'])->name('index');
// });
// });