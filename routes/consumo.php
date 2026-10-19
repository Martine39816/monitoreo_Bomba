<?php

use App\Http\Controllers\ConsumoController;
use Illuminate\Support\Facades\Route;

// Consumo de agua estimado.
// Consulta: todos los roles autenticados (el Director ve los reportes de su centro;
// el acceso por centro lo controla BombaPolicy@view dentro del controlador).
Route::middleware(['auth'])->group(function () {
    Route::get('/consumo', [ConsumoController::class, 'index'])->name('consumo.index');
});

// Cambiar el tiempo de llenado: Administrador y Técnico.
Route::middleware(['auth', 'role:administrador,tecnico'])->group(function () {
    Route::patch('/bombas/{bomba}/tiempo-llenado', [ConsumoController::class, 'guardarTiempo'])
        ->name('consumo.tiempo');
});