<?php

namespace App\Http\Controllers;

use App\Models\CentroSalud;
use App\Support\TextPatterns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CentroSaludController extends Controller
{
    public function index(): View
    {
        $centros = CentroSalud::orderBy('nombre')->get();

        return view('centros.index', compact('centros'));
    }

    public function create(): View
    {
        return view('centros.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarDatos($request);

        CentroSalud::create($validated);

        return redirect()->route('centros.index')->with('status', 'Centro de salud registrado correctamente.');
    }

    public function show(CentroSalud $centro): View
    {
        $centro->load('usuarios', 'bombas', 'dispositivosIot');

        return view('centros.show', compact('centro'));
    }

    public function edit(CentroSalud $centro): View
    {
        return view('centros.edit', compact('centro'));
    }

    public function update(Request $request, CentroSalud $centro): RedirectResponse
    {
        $validated = $this->validarDatos($request, $centro->id);

        $centro->update($validated);

        return redirect()->route('centros.index')->with('status', 'Centro de salud actualizado correctamente.');
    }

    public function destroy(CentroSalud $centro): RedirectResponse
    {
        if ($centro->usuarios()->exists() || $centro->bombas()->exists() || $centro->dispositivosIot()->exists()) {
            return back()->withErrors([
                'delete' => 'No se puede eliminar este centro: todavía tiene usuarios, bombas o dispositivos asignados.',
            ]);
        }

        $centro->delete();

        return redirect()->route('centros.index')->with('status', 'Centro de salud eliminado.');
    }

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:45', 'regex:'.TextPatterns::CODIGO, Rule::unique('centros_salud', 'codigo')->ignore($ignorarId)],
            'nombre' => ['required', 'string', 'max:45', 'regex:'.TextPatterns::NOMBRE_EQUIPO],
            'direccion' => ['required', 'string', 'max:255', 'regex:'.TextPatterns::DIRECCION],
            'telefono' => ['nullable', 'regex:'.TextPatterns::TELEFONO_BOLIVIA],
        ], [
            'codigo.regex' => 'El código solo puede contener letras, números, guiones y puntos (sin espacios).',
            'nombre.regex' => 'El nombre no puede tener espacios dobles ni caracteres especiales.',
            'direccion.regex' => 'La dirección tiene caracteres no permitidos o espacios dobles.',
            'telefono.regex' => 'El teléfono debe ser un celular boliviano válido: 8 dígitos, empezando con 6 o 7 (ej. 71234567).',
        ]);
    }
}