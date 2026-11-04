<?php

namespace App\Providers;

use App\Models\Bomba;
use App\Observers\BombaObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Limite propio y mas estricto para encender/apagar bombas:
        // es la accion mas critica del sistema (controla equipo fisico real).
        // Uso en rutas: Route::middleware('throttle:control-bomba')->...
        RateLimiter::for('control-bomba', function ($request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Limite general para la futura API de dispositivos IoT / app movil
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Comparte el conteo de alertas pendientes con la barra de navegacion
        View::composer(
            'layouts.navigation',
            \App\View\Composers\AlertaComposer::class
        );

        // Registra en eventos_bomba cada vez que una bomba se enciende/apaga,
        // para poder calcular despues tiempo de funcionamiento y ciclos.
        Bomba::observe(BombaObserver::class);
    }
}