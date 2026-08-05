<?php

namespace App\Http\Controllers;

use App\Models\CentroSalud;
use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('centroSalud')->orderBy('id', 'desc')->get();

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
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:usuarios,correo'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol' => ['required', Rule::in(User::ROLES)],
            'centros_salud_id' => ['required', 'exists:centros_salud,id'],
            'password' => ['required', 'confirmed', PasswordPolicy::reglas()],
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

    public function show(User $user): View
    {
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
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', Rule::unique('usuarios', 'correo')->ignore($user->id)],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol' => ['required', Rule::in(User::ROLES)],
            'centros_salud_id' => ['required', 'exists:centros_salud,id'],
            'estado' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', PasswordPolicy::reglas()],
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

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Usuario eliminado.');
    }
}