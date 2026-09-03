<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login para la app Android. Recibe correo + password,
     * devuelve un token Sanctum + datos del usuario (incluyendo rol).
     */
    public function login(Request $request)
    {
        $request->validate([
            'correo' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = User::where('correo', $request->correo)->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            throw ValidationException::withMessages([
                'correo' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (! $usuario->estado) {
            throw ValidationException::withMessages([
                'correo' => ['Esta cuenta se encuentra desactivada.'],
            ]);
        }

        // Token con nombre descriptivo (útil si luego quieres revocar
        // solo tokens de la app móvil, distinguiéndolos por nombre).
        $token = $usuario->createToken('app-movil-android')->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'correo' => $usuario->correo,
                'rol' => $usuario->rol,
                'centros_salud_id' => $usuario->centros_salud_id,
            ],
        ]);
    }

    /**
     * Logout: revoca únicamente el token actual usado en esta request
     * (no todos los tokens del usuario, por si tiene sesión en otro dispositivo).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['mensaje' => 'Sesión cerrada correctamente.']);
    }
}