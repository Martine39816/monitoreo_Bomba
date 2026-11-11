<?php

namespace App\Http\Middleware;

use App\Models\DispositivoIot;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica peticiones de dispositivos IoT (ESP32/PLC) usando una API key
 * propia del dispositivo, independiente de las cuentas de usuario.
 *
 * El dispositivo debe enviar la cabecera: X-Device-Key: <clave>
 */
class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $claveEnviada = $request->header('X-Device-Key');

        if (! $claveEnviada) {
            return response()->json(['message' => 'Falta la cabecera X-Device-Key.'], 401);
        }

        $claveHasheada = hash('sha256', $claveEnviada);

        $dispositivo = DispositivoIot::where('api_key', $claveHasheada)->first();

        if (! $dispositivo) {
            return response()->json(['message' => 'API key invalida.'], 401);
        }

        if ($dispositivo->estado !== 'activo') {
            return response()->json(['message' => 'Dispositivo inactivo o suspendido.'], 403);
        }

        // Deja el dispositivo autenticado disponible para el controlador
        $request->attributes->set('dispositivo', $dispositivo);

        return $next($request);
    }
}