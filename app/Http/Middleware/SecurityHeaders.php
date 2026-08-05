<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega cabeceras de seguridad HTTP a toda respuesta.
 *
 * La politica CSP estricta solo se aplica en produccion (assets compilados
 * con `npm run build`). En 'local' con Vite (`npm run dev`) se usa una
 * version relajada, porque el servidor de desarrollo de Vite necesita
 * scripts inline y una conexion websocket que 'self' bloquearia,
 * rompiendo Alpine.js (el menu desplegable, por ejemplo).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', $this->construirCsp());

        return $response;
    }

    private function construirCsp(): string
    {
        if (app()->environment('production')) {
            return "default-src 'self'; ".
                "script-src 'self'; ".
                "style-src 'self' 'unsafe-inline'; ".
                "img-src 'self' data:; ".
                "font-src 'self'; ".
                "connect-src 'self'; ".
                "frame-ancestors 'none'; ".
                "base-uri 'self'; ".
                "form-action 'self';";
        }

        // Version relajada para desarrollo local con Vite (npm run dev)
        return "default-src 'self'; ".
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ".
            "style-src 'self' 'unsafe-inline'; ".
            "img-src 'self' data:; ".
            "font-src 'self' data:; ".
            "connect-src 'self' ws://127.0.0.1:5173 ws://localhost:5173 http://127.0.0.1:5173 http://localhost:5173; ".
            "frame-ancestors 'none'; ".
            "base-uri 'self'; ".
            "form-action 'self';";
    }
}
