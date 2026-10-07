<?php
use App\Http\Controllers\Admin\EventTemplateAssetController;
use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\EventValidityController;
use App\Http\Controllers\Template\TemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'role:superadmin'])
    ->group(function () {
        Route::redirect('/', '/superadmin/dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Imágenes de la plantilla de una boda concreta (el monograma, un mapa,
        // una secuencia de animación). Sólo el superadmin.
        Route::get('/personalizacion', [EventTemplateAssetController::class, 'index'])
            ->name('events.assets.index');
        Route::get('/eventos/{event}/imagenes', [EventTemplateAssetController::class, 'edit'])
            ->name('events.assets.edit');
        Route::post('/eventos/{event}/imagenes', [EventTemplateAssetController::class, 'update'])
            ->name('events.assets.update');
        Route::delete('/eventos/{event}/imagenes', [EventTemplateAssetController::class, 'reset'])
            ->name('events.assets.reset');

        // Hasta cuándo sirve una invitación, y su interruptor.
        Route::get('/eventos/{event}/vigencia', [EventValidityController::class, 'edit'])
            ->name('events.validity.edit');
        Route::put('/eventos/{event}/vigencia', [EventValidityController::class, 'update'])
            ->name('events.validity.update');
    });

Route::controller(TemplateController::class)
    ->prefix('templates')
    ->name('templates.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');

        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::patch('/{id}/toggle-status', 'toggleStatus')->name('toggle-status');
    });