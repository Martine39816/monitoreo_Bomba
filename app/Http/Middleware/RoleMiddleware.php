<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reemplaza a AdminMiddleware. Uso en rutas:
 *   Route::middleware(['auth', 'role:administrador'])->group(...)
 *   Route::middleware(['auth', 'role:administrador,tecnico'])->group(...)
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        // Se rechaza tambien a las cuentas desactivadas (estado = false), por si
        // la cuenta fue desactivada mientras la sesion ya estaba abierta.
        if (! $usuario || ! $usuario->estado || ! in_array($usuario->rol, $roles, true)) {
            abort(403, 'No tienes permisos para acceder a esta seccion.');
        }

        return $next($request);
    }
}
