<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * IMPORTANTE: esta pantalla ya NO es de auto-registro publico.
 * La ruta esta protegida por ['auth', 'role:administrador'] en routes/auth.php,
 * asi que solo un Administrador logueado puede crear nuevos usuarios.
 * Esto es una decision de seguridad: un sistema que controla bombas de agua
 * no debe permitir que cualquier visitante se cree una cuenta.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $centros = \App\Models\CentroSalud::orderBy('nombre')->get();

        return view('auth.register', compact('centros'));
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:120', 'unique:usuarios,correo'],
            'rol' => ['required', Rule::in(User::ROLES)],
            'centros_salud_id' => ['required', 'exists:centros_salud,id'],
            'password' => ['required', 'confirmed', PasswordPolicy::reglas()],
        ]);

        $user = User::create([
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'correo' => $validated['email'],
            'password' => $validated['password'], // se hashea solo por el cast 'hashed' del modelo
            'rol' => $validated['rol'],
            'centros_salud_id' => $validated['centros_salud_id'],
            'estado' => true,
        ]);

        event(new Registered($user));

        // No se hace login automatico: quien crea la cuenta es el Administrador,
        // no la persona duena de la nueva cuenta.
        return redirect()->route('users.index')
            ->with('status', 'Usuario creado correctamente.');
    }
}
