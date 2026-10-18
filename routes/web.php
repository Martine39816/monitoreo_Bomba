<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BombaController;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Gestion de usuarios (crear/editar/eliminar): solo Administrador
Route::middleware(['auth', 'role:administrador'])->group(function () {
    Route::resource('users', UserController::class)->except(['index', 'show']);
});

// Consulta de usuarios: Administrador (todos) y Director (solo técnicos de su centro)
Route::middleware(['auth', 'role:administrador,director'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
});

// Gestión de bombas (crear/editar/eliminar): Administrador y Técnico.
// Se registra primero para que /bombas/crear no caiga en /bombas/{bomba}.
Route::middleware(['auth', 'role:administrador,tecnico'])->group(function () {
    Route::resource('bombas', BombaController::class)->except(['index', 'show']);
});

// Consulta de bombas: Administrador y Técnico (todas) y Director (solo las de su centro).
// El filtrado por centro se aplica en BombaController::index y la Policy de show.
Route::middleware(['auth', 'role:administrador,tecnico,director'])->group(function () {
    Route::resource('bombas', BombaController::class)->only(['index', 'show']);
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

// Consulta de alertas: todos los roles autenticados.
// El Director solo consulta: no puede atender ni cambiar el estado de una alerta
// (la restriccion se aplica en el servidor mediante el middleware de rol).
Route::middleware(['auth'])->group(function () {
    Route::get('/alertas', [\App\Http\Controllers\AlertaController::class, 'index'])->name('alertas.index');
    Route::get('/alertas/{alerta}', [\App\Http\Controllers\AlertaController::class, 'show'])->name('alertas.show');
    Route::patch('/alertas/{alerta}/atender', [\App\Http\Controllers\AlertaController::class, 'atender'])
        ->middleware('role:administrador,tecnico')
        ->name('alertas.atender');
});

// Reportes: los tres roles. El administrador consulta todos los centros;
// el técnico y el director quedan limitados a su propio centro en el
// servidor (ReporteService::resolverCentro), sin confiar en el navegador.
// El comparativo entre centros se registrará en su fase, solo para administrador.
Route::middleware(['auth', 'role:administrador,tecnico,director'])->group(function () {
    Route::get('/reportes', [App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index');

    // El informe general se registra antes de la ruta generica de exportacion
    // porque su descarga tambien tiene la forma /reportes/{algo}/exportar/{formato}.
    Route::get('/reportes/informe', [App\Http\Controllers\ReporteController::class, 'informe'])->name('reportes.informe');
    Route::get('/reportes/informe/exportar/{formato}', [App\Http\Controllers\ReporteController::class, 'exportarInforme'])
        ->name('reportes.informe.exportar');

    Route::get('/reportes/bombas', [App\Http\Controllers\ReporteController::class, 'bombas'])->name('reportes.bombas');
    Route::get('/reportes/lecturas', [App\Http\Controllers\ReporteController::class, 'lecturas'])->name('reportes.lecturas');
    Route::get('/reportes/alertas', [App\Http\Controllers\ReporteController::class, 'alertas'])->name('reportes.alertas');
    Route::get('/reportes/mantenimientos', [App\Http\Controllers\ReporteController::class, 'mantenimientos'])->name('reportes.mantenimientos');

    // Descarga de un reporte en PDF o Excel. Se registra despues de las rutas
    // anteriores porque su patron tiene mas segmentos y no debe interceptarlas.
    Route::get('/reportes/{reporte}/exportar/{formato}', [App\Http\Controllers\ReporteController::class, 'exportar'])
        ->name('reportes.exportar');
});

// Control remoto de bombas: Administrador y Técnico
Route::middleware(['auth', 'role:administrador,tecnico', 'throttle:control-bomba'])->group(function () {
    Route::post('/bombas/{bomba}/encender', [\App\Http\Controllers\BombaController::class, 'encender'])->name('bombas.encender');
    Route::post('/bombas/{bomba}/apagar', [\App\Http\Controllers\BombaController::class, 'apagar'])->name('bombas.apagar');
});

require __DIR__.'/auth.php';