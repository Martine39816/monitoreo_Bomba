<?php

namespace App\Http\Controllers;

use App\Models\Bomba;
use App\Models\CentroSalud;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BombaController extends Controller
{
    use AuthorizesRequests;

    private const ESTADOS = ['activo', 'inactivo', 'mantenimiento', 'fuera_servicio', 'desconectado'];

    public function index(): View
    {
        $bombas = Bomba::with('centroSalud')->orderBy('id', 'desc')->get();

        return view('bombas.index', compact('bombas'));
    }

    public function create(): View
    {
        $centros = CentroSalud::orderBy('nombre')->get();

        return view('bombas.create', ['centros' => $centros, 'estados' => self::ESTADOS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarDatos($request);

        Bomba::create($validated);

        return redirect()->route('bombas.index')->with('status', 'Bomba registrada correctamente.');
    }

    public function show(Bomba $bomba): View
    {
        $bomba->load('centroSalud', 'sensores');

        $salud = \App\Models\VistaSaludBomba::where('bomba_id', $bomba->id)->first();

        return view('bombas.show', compact('bomba', 'salud'));
    }

    public function edit(Bomba $bomba): View
    {
        $centros = CentroSalud::orderBy('nombre')->get();

        return view('bombas.edit', ['bomba' => $bomba, 'centros' => $centros, 'estados' => self::ESTADOS]);
    }

    public function update(Request $request, Bomba $bomba): RedirectResponse
    {
        $validated = $this->validarDatos($request, $bomba->id);

        $bomba->update($validated);

        return redirect()->route('bombas.index')->with('status', 'Bomba actualizada correctamente.');
    }

    public function destroy(Bomba $bomba): RedirectResponse
    {
        $bomba->delete();

        return redirect()->route('bombas.index')->with('status', 'Bomba eliminada.');
    }

    public function encender(Request $request, Bomba $bomba): RedirectResponse
    {
        $this->authorize('controlar', $bomba);

        $bomba->update(['encendido' => true]);

        return redirect()->route('bombas.show', $bomba->id)
            ->with('status', 'Bomba encendida correctamente.');
    }

    public function apagar(Request $request, Bomba $bomba): RedirectResponse
    {
        $this->authorize('controlar', $bomba);

        $bomba->update(['encendido' => false]);

        return redirect()->route('bombas.show', $bomba->id)
            ->with('status', 'Bomba apagada correctamente.');
    }

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('bombas', 'codigo')->ignore($ignorarId)],
            'nombre' => ['required', 'string', 'max:100'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'serie' => ['required', 'string', 'max:80', Rule::unique('bombas', 'serie')->ignore($ignorarId)],
            'potencia_hp' => ['nullable', 'numeric', 'gt:0'],
            'voltaje_nominal' => ['nullable', 'numeric', 'gt:0'],
            'corriente_nominal' => ['nullable', 'numeric', 'gte:0'],
            'caudal_min' => ['nullable', 'numeric', 'gte:0'],
            'caudal_max' => ['nullable', 'numeric', 'gte:0'],
            'altura_maxima' => ['nullable', 'numeric', 'gte:0'],
            'temperatura_maxima' => ['nullable', 'numeric'],
            'presion_maxima' => ['nullable', 'numeric', 'gte:0'],
            'fecha_instalacion' => ['nullable', 'date'],
            'modo_operacion' => ['required', 'boolean'],
            'encendido' => ['required', 'boolean'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'tanque_codigo' => ['nullable', 'string', 'max:30'],
            'tanque_capacidad_litros' => ['nullable', 'numeric', 'gt:0'],
            'tanque_altura_metros' => ['nullable', 'numeric', 'gte:0'],
            'tanque_diametro_metros' => ['nullable', 'numeric', 'gte:0'],
            'observaciones' => ['nullable', 'string'],
            'centros_salud_id' => ['required', 'exists:centros_salud,id'],
        ]);
    }
}