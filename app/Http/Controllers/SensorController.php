<?php

namespace App\Http\Controllers;

use App\Models\Bomba;
use App\Models\DispositivoIot;
use App\Models\Sensor;
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
            'codigo' => ['required', 'string', 'max:30', Rule::unique('sensores', 'codigo')->ignore($ignorarId)],
            'nombre' => ['required', 'string', 'max:120'],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'unidad_medida' => ['nullable', 'string', 'max:30'],
            'valor_minimo' => ['nullable', 'numeric', 'lt:valor_maximo'],
            'valor_maximo' => ['nullable', 'numeric', 'gt:valor_minimo'],
            'precision_sensor' => ['nullable', 'numeric'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'fecha_instalacion' => ['nullable', 'date'],
            'ubicacion' => ['nullable', 'string', 'max:100'],
            'dispositivos_iot_id' => ['required', 'exists:dispositivos_iot,id'],
            'bombas_id' => ['required', 'exists:bombas,id'],
        ]);
    }
}