<?php
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RsvpController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;


// Route::prefix('admin')
//     ->name('admin.')
//     ->middleware(['auth', 'role:admin|organizer'])
//     ->group(function () {
//         Route::redirect('/', '/admin/dashboard');
//         Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
//     });

// Route::prefix('users')->name('users.')->group(function () {
//     Route::get('/', [UserController::class, 'index'])->name('index');
//     Route::get('/create', [UserController::class, 'create'])->name('create');
//     Route::get('/{id}', [UserController::class, 'show'])->name('show');
//     Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');

//     Route::post('/', [UserController::class, 'store'])->name('store');
//     Route::put('/{id}', [UserController::class, 'update'])->name('update');
//     Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
// });