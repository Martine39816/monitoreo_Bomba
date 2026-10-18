<?php

namespace App\Http\Controllers;

use App\Models\CentroSalud;
use App\Models\User;
use App\Support\PasswordPolicy;
use App\Support\TextPatterns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();

        $users = User::with('centroSalud')
            // El Director (no administrador) solo ve los técnicos de su centro.
            ->when(! $usuario->esAdministrador(), function ($q) use ($usuario) {
                $q->where('rol', User::ROL_TECNICO)
                    ->where('centros_salud_id', $usuario->centros_salud_id);
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $centros = CentroSalud::orderBy('nombre')->get();

        return view('users.create', compact('centros'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'regex:'.TextPatterns::NOMBRE_PERSONA],
            'apellido' => ['required', 'string', 'max:100', 'regex:'.TextPatterns::NOMBRE_PERSONA],
            'email' => ['required', 'email', 'max:120', 'unique:usuarios,correo'],
            'telefono' => ['nullable', 'regex:'.TextPatterns::TELEFONO_BOLIVIA],
            'rol' => ['required', Rule::in(User::ROLES)],
            'centros_salud_id' => ['required', 'exists:centros_salud,id'],
            'password' => ['required', 'confirmed', PasswordPolicy::reglas()],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios simples (sin números).',
            'apellido.regex' => 'El apellido solo puede contener letras y espacios simples (sin números).',
            'telefono.regex' => 'El teléfono debe ser un celular boliviano válido: 8 dígitos, empezando con 6 o 7 (ej. 71234567).',
        ]);

        User::create([
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'correo' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'rol' => $validated['rol'],
            'centros_salud_id' => $validated['centros_salud_id'],
            'password' => $validated['password'], // el cast 'hashed' del modelo lo encripta
            'estado' => true,
        ]);

        return redirect()->route('users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function show(Request $request, User $user): View
    {
        $this->verificarAcceso($request, $user);

        $user->load('centroSalud');

        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $centros = CentroSalud::orderBy('nombre')->get();

        return view('users.edit', compact('user', 'centros'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'regex:'.TextPatterns::NOMBRE_PERSONA],
            'apellido' => ['required', 'string', 'max:100', 'regex:'.TextPatterns::NOMBRE_PERSONA],
            'email' => ['required', 'email', 'max:120', Rule::unique('usuarios', 'correo')->ignore($user->id)],
            'telefono' => ['nullable', 'regex:'.TextPatterns::TELEFONO_BOLIVIA],
            'rol' => ['required', Rule::in(User::ROLES)],
            'centros_salud_id' => ['required', 'exists:centros_salud,id'],
            'estado' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', PasswordPolicy::reglas()],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios simples (sin números).',
            'apellido.regex' => 'El apellido solo puede contener letras y espacios simples (sin números).',
            'telefono.regex' => 'El teléfono debe ser un celular boliviano válido: 8 dígitos, empezando con 6 o 7 (ej. 71234567).',
        ]);

        $user->fill([
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'correo' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'rol' => $validated['rol'],
            'centros_salud_id' => $validated['centros_salud_id'],
            'estado' => $validated['estado'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors(['delete' => 'No puedes eliminar tu propia cuenta mientras estas conectado.']);
        }

        // Evitar que queden cero administradores en el sistema.
        if ($user->rol === 'administrador') {
            $totalAdministradores = User::where('rol', 'administrador')->count();

            if ($totalAdministradores <= 1) {
                return back()->withErrors(['delete' => 'No se puede eliminar el último administrador del sistema.']);
            }
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Usuario eliminado.');
    }

    /**
     * El Director solo puede ver el detalle de los técnicos de su centro.
     * El Administrador no tiene restricción.
     */
    private function verificarAcceso(Request $request, User $user): void
    {
        $usuario = $request->user();

        if ($usuario->esAdministrador()) {
            return;
        }

        abort_if(
            $user->rol !== User::ROL_TECNICO || $user->centros_salud_id !== $usuario->centros_salud_id,
            403,
            'No tienes permiso para ver este usuario.'
        );
    }
}