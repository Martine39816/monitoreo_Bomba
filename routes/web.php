<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Gestion de usuarios: solo Administrador
Route::middleware(['auth', 'role:administrador'])->group(function () {
    Route::resource('users', UserController::class);
});

// Ejemplo de como se protegera el control remoto de bombas (Administrador y Tecnico)
// cuando se cree BombaController — dejar como referencia:
//
// Route::middleware(['auth', 'role:administrador,tecnico', 'throttle:control-bomba'])->group(function () {
//     Route::post('/bombas/{bomba}/encender', [BombaController::class, 'encender'])->name('bombas.encender');
//     Route::post('/bombas/{bomba}/apagar', [BombaController::class, 'apagar'])->name('bombas.apagar');
// });

require __DIR__.'/auth.php';
