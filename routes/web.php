<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BombaController;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
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

// Gestión de bombas: Administrador y Técnico
Route::middleware(['auth', 'role:administrador,tecnico'])->group(function () {
    Route::resource('bombas', BombaController::class);
});

// Gestión de centros de salud: Administrador y Técnico
// (el Técnico registra el centro al momento de instalar el sistema ahí)
Route::middleware(['auth', 'role:administrador,tecnico'])->group(function () {
    Route::resource('centros', \App\Http\Controllers\CentroSaludController::class);
});

// Gestión de dispositivos IoT: Administrador y Técnico
Route::middleware(['auth', 'role:administrador,tecnico'])->group(function () {
    Route::resource('dispositivos', \App\Http\Controllers\DispositivoIotController::class);
});

Route::middleware(['auth', 'role:administrador,tecnico'])->group(function () {
    Route::post('/dispositivos/{dispositivo}/generar-api-key', [\App\Http\Controllers\DispositivoIotController::class, 'generarApiKey'])
        ->name('dispositivos.generar-api-key');
});

// Gestión de sensores: Administrador y Técnico
Route::middleware(['auth', 'role:administrador,tecnico'])->group(function () {
    Route::resource('sensores', \App\Http\Controllers\SensorController::class)
        ->parameters(['sensores' => 'sensor']);
});

// Gestión de mantenimientos: Administrador y Técnico
Route::middleware(['auth', 'role:administrador,tecnico'])->group(function () {
    Route::resource('mantenimientos', \App\Http\Controllers\MantenimientoController::class);
});

// Insertar lectura de prueba: Administrador y Técnico
// (va antes de las rutas de consulta, para no chocar con futuras rutas con parametro)
Route::middleware(['auth', 'role:administrador,tecnico'])->group(function () {
    Route::get('/lecturas/crear', [\App\Http\Controllers\LecturaController::class, 'create'])->name('lecturas.create');
    Route::post('/lecturas', [\App\Http\Controllers\LecturaController::class, 'store'])->name('lecturas.store');
});

// Consulta de lecturas: todos los roles autenticados
Route::middleware(['auth'])->group(function () {
    Route::get('/lecturas', [\App\Http\Controllers\LecturaController::class, 'index'])->name('lecturas.index');
});

// Reportar un problema manualmente: todos los roles autenticados
// (el Director reporta problemas aunque no controle la bomba directamente)
// IMPORTANTE: esta ruta va ANTES de /alertas/{alerta}, si no, "crear" se
// interpreta como un ID de alerta y da 404.
Route::middleware(['auth'])->group(function () {
    Route::get('/alertas/crear', [\App\Http\Controllers\AlertaController::class, 'create'])->name('alertas.create');
    Route::post('/alertas', [\App\Http\Controllers\AlertaController::class, 'store'])->name('alertas.store');
});

// Consulta y atención de alertas: todos los roles autenticados
Route::middleware(['auth'])->group(function () {
    Route::get('/alertas', [\App\Http\Controllers\AlertaController::class, 'index'])->name('alertas.index');
    Route::get('/alertas/{alerta}', [\App\Http\Controllers\AlertaController::class, 'show'])->name('alertas.show');
    Route::patch('/alertas/{alerta}/atender', [\App\Http\Controllers\AlertaController::class, 'atender'])->name('alertas.atender');
});

// Control remoto de bombas: Administrador y Técnico
Route::middleware(['auth', 'role:administrador,tecnico', 'throttle:control-bomba'])->group(function () {
    Route::post('/bombas/{bomba}/encender', [\App\Http\Controllers\BombaController::class, 'encender'])->name('bombas.encender');
    Route::post('/bombas/{bomba}/apagar', [\App\Http\Controllers\BombaController::class, 'apagar'])->name('bombas.apagar');
});

require __DIR__.'/auth.php';