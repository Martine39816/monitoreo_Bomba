<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Politica de contrasena centralizada. Usar en todos los formularios
 * que definan o cambien contrasenas (registro, cambio de password, etc.)
 */
class PasswordPolicy
{
    public static function reglas(): Password
    {
        return Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();
    }
}
