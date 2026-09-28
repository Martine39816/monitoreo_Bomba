<?php

namespace App\Http\Controllers;

use App\Models\Bomba;
use App\Models\DispositivoIot;
use App\Models\Sensor;
use App\Support\TextPatterns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SensorController extends Controller
{
    private const TIPOS = ['temperatura', 'vibracion', 'corriente', 'presion', 'nivel'];
    private const ESTADOS = ['activo', 'inactivo', 'mantenimiento', 'fuera_servicio', 'desconectado'];

    public function index(): View
    {
        $sensores = Sensor::with('bomba', 'dispositivoIot')->orderBy('id', 'desc')->get();

        return view('sensores.index', compact('sensores'));
    }

    public function create(): View
    {
        return view('sensores.create', [
            'bombas' => Bomba::orderBy('nombre')->get(),
            'dispositivos' => DispositivoIot::orderBy('nombre')->get(),
            'tipos' => self::TIPOS,
            'estados' => self::ESTADOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarDatos($request);

        Sensor::create($validated);

        return redirect()->route('sensores.index')->with('status', 'Sensor registrado correctamente.');
    }

    public function show(Sensor $sensor): View
    {
        $sensor->load('bomba', 'dispositivoIot', 'lecturas');

        return view('sensores.show', compact('sensor'));
    }

    public function edit(Sensor $sensor): View
    {
        return view('sensores.edit', [
            'sensor' => $sensor,
            'bombas' => Bomba::orderBy('nombre')->get(),
            'dispositivos' => DispositivoIot::orderBy('nombre')->get(),
            'tipos' => self::TIPOS,
            'estados' => self::ESTADOS,
        ]);
    }

    public function update(Request $request, Sensor $sensor): RedirectResponse
    {
        $validated = $this->validarDatos($request, $sensor->id);

        $sensor->update($validated);

        return redirect()->route('sensores.index')->with('status', 'Sensor actualizado correctamente.');
    }

    public function destroy(Sensor $sensor): RedirectResponse
    {
        $sensor->delete();

        return redirect()->route('sensores.index')->with('status', 'Sensor eliminado.');
    }

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:30', 'regex:'.TextPatterns::CODIGO, Rule::unique('sensores', 'codigo')->ignore($ignorarId)],
            'nombre' => ['required', 'string', 'max:120', 'regex:'.TextPatterns::NOMBRE_EQUIPO],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'marca' => ['nullable', 'string', 'max:80', 'regex:'.TextPatterns::MARCA_MODELO_SERIE],
            'modelo' => ['nullable', 'string', 'max:80', 'regex:'.TextPatterns::MARCA_MODELO_SERIE],
            'unidad_medida' => ['nullable', 'string', 'max:30'],
            'valor_minimo' => ['nullable', 'numeric', 'lt:valor_maximo'],
            'valor_maximo' => ['nullable', 'numeric', 'gt:valor_minimo'],
            'precision_sensor' => ['nullable', 'numeric'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'fecha_instalacion' => ['nullable', 'date'],
            'ubicacion' => ['nullable', 'string', 'max:100', 'regex:'.TextPatterns::NOMBRE_EQUIPO],
            'dispositivos_iot_id' => ['required', 'exists:dispositivos_iot,id'],
            'bombas_id' => ['required', 'exists:bombas,id'],
        ], [
            'codigo.regex' => 'El código solo puede contener letras, números, guiones y puntos (sin espacios).',
            'nombre.regex' => 'El nombre no puede tener espacios dobles ni caracteres especiales.',
            'marca.regex' => 'La marca no puede tener espacios dobles ni caracteres especiales.',
            'modelo.regex' => 'El modelo no puede tener espacios dobles ni caracteres especiales.',
            'ubicacion.regex' => 'La ubicación no puede tener espacios dobles ni caracteres especiales.',
            'valor_minimo.lt' => 'El valor mínimo debe ser menor que el valor máximo.',
            'valor_maximo.gt' => 'El valor máximo debe ser mayor que el valor mínimo.',
        ]);
    }
}