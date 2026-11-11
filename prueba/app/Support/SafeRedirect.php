<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

/**
 * Evita redirecciones abiertas (Open Redirect): valida que la URL destino
 * pertenezca al mismo dominio de la aplicacion antes de redirigir.
 *
 * Uso:
 *   return SafeRedirect::to($request->query('next'), fallback: route('dashboard'));
 */
class SafeRedirect
{
    public static function to(?string $url, string $fallback = '/'): RedirectResponse
    {
        if (! $url || ! self::esUrlSegura($url)) {
            return redirect($fallback);
        }

        return redirect($url);
    }

    public static function esUrlSegura(string $url): bool
    {
        // Rutas relativas (empiezan con /) son seguras siempre que no sean
        // "protocol-relative" (//evil.com), que el navegador trata como externas.
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && $host === parse_url(URL::to('/'), PHP_URL_HOST);
    }
}
