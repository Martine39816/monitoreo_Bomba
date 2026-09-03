<?php

namespace App\Http\Controllers;

use App\Models\CentroSalud;
use App\Models\DispositivoIot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DispositivoIotController extends Controller
{
    private const ESTADOS = ['activo', 'inactivo', 'mantenimiento', 'fuera_servicio', 'desconectado'];

    public function index(): View
    {
        $dispositivos = DispositivoIot::with('centroSalud')->orderBy('id', 'desc')->get();

        return view('dispositivos.index', compact('dispositivos'));
    }

    public function create(): View
    {
        $centros = CentroSalud::orderBy('nombre')->get();

        return view('dispositivos.create', ['centros' => $centros, 'estados' => self::ESTADOS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarDatos($request);

        DispositivoIot::create($validated);

        return redirect()->route('dispositivos.index')->with('status', 'Dispositivo IoT registrado correctamente.');
    }

    public function show(DispositivoIot $dispositivo): View
    {
        $dispositivo->load('centroSalud', 'sensores');

        return view('dispositivos.show', compact('dispositivo'));
    }

    public function edit(DispositivoIot $dispositivo): View
    {
        $centros = CentroSalud::orderBy('nombre')->get();

        return view('dispositivos.edit', ['dispositivo' => $dispositivo, 'centros' => $centros, 'estados' => self::ESTADOS]);
    }

    public function update(Request $request, DispositivoIot $dispositivo): RedirectResponse
    {
        $validated = $this->validarDatos($request, $dispositivo->id);

        $dispositivo->update($validated);

        return redirect()->route('dispositivos.index')->with('status', 'Dispositivo IoT actualizado correctamente.');
    }

    public function destroy(DispositivoIot $dispositivo): RedirectResponse
    {
        if ($dispositivo->sensores()->exists()) {
            return back()->withErrors([
                'delete' => 'No se puede eliminar: este dispositivo todavía tiene sensores asociados.',
            ]);
        }

        $dispositivo->delete();

        return redirect()->route('dispositivos.index')->with('status', 'Dispositivo IoT eliminado.');
    }

    /**
     * Genera una nueva API key para el dispositivo (para autenticar al ESP32 real).
     */
    public function generarApiKey(DispositivoIot $dispositivo): RedirectResponse
    {
        $claveTextoPlano = $dispositivo->generarApiKey();

        return redirect()->route('dispositivos.show', $dispositivo->id)
            ->with('api_key_generada', $claveTextoPlano)
            ->with('status', 'API key generada. Cópiala ahora, no se volverá a mostrar.');
    }

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('dispositivos_iot', 'codigo')->ignore($ignorarId)],
            'nombre' => ['required', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'tipo_dispositivo' => ['nullable', 'string', 'max:30'],
            'puerto' => ['nullable', 'integer'],
            'direccion_ip' => ['nullable', 'string', 'max:50'],
            'mac_address' => ['nullable', 'string', 'max:50'],
            'firmware' => ['nullable', 'string', 'max:50'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'centros_salud_id' => ['required', 'exists:centros_salud,id'],
        ]);
    }
}