<?php

use App\Http\Controllers\Api\AlertaApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BombaApiController;
use App\Http\Controllers\Api\DispositivoApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ConsumoApiController;


// ---- Rutas para el ESP32 (autenticación por dispositivo) ----
Route::middleware('device.auth')->group(function () {
    Route::post('/lecturas', [DispositivoApiController::class, 'guardarLectura']);
    Route::get('/bombas/{id}/estado', [DispositivoApiController::class, 'estadoBomba']);
    Route::get('/bombas', [BombaApiController::class, 'index']); 
    

});

// ---- Rutas para la app Android (autenticación por usuario, Sanctum) ----
Route::prefix('mobile')->group(function () {

    // Pública: aquí es donde se genera el token
    Route::post('/login', [AuthController::class, 'login']);

    // Protegidas: requieren token Bearer válido en el header Authorization
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/bombas', [BombaApiController::class, 'index']);
        
        // Control remoto desde Android
        Route::post(
            '/bombas/{bomba}/encender',
            [BombaApiController::class, 'encender']
        )->middleware('throttle:control-bomba');

        Route::post(
            '/bombas/{bomba}/apagar',
            [BombaApiController::class, 'apagar']
        )->middleware('throttle:control-bomba');

        Route::get('/alertas', [AlertaApiController::class, 'index']);
        Route::post('/alertas/{alerta}/solucionar', [AlertaApiController::class, 'solucionar']);
        Route::get('/bombas/{bomba}/consumo', [ConsumoApiController::class, 'show']);
    });
});