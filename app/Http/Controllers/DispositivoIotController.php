<?php

namespace App\Http\Controllers;

use App\Models\CentroSalud;
use App\Models\DispositivoIot;
use App\Support\TextPatterns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DispositivoIotController extends Controller
{
    private const ESTADOS = ['activo', 'inactivo', 'mantenimiento', 'fuera_servicio', 'desconectado'];

    public function index(Request $request): View
    {
        $dispositivos = DispositivoIot::with('centroSalud')
            ->when(! $request->user()->esAdministrador(), function ($q) use ($request) {
                $q->where('centros_salud_id', $request->user()->centros_salud_id);
            })
            ->orderBy('id', 'desc')
            ->get();

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

    public function show(Request $request, DispositivoIot $dispositivo): View
    {
        $this->verificarCentro($request, $dispositivo);

        $dispositivo->load('centroSalud', 'sensores');

        return view('dispositivos.show', compact('dispositivo'));
    }

    public function edit(Request $request, DispositivoIot $dispositivo): View
    {
        $this->verificarCentro($request, $dispositivo);

        $centros = CentroSalud::orderBy('nombre')->get();

        return view('dispositivos.edit', ['dispositivo' => $dispositivo, 'centros' => $centros, 'estados' => self::ESTADOS]);
    }

    public function update(Request $request, DispositivoIot $dispositivo): RedirectResponse
    {
        $this->verificarCentro($request, $dispositivo);

        $validated = $this->validarDatos($request, $dispositivo->id);

        $dispositivo->update($validated);

        return redirect()->route('dispositivos.index')->with('status', 'Dispositivo IoT actualizado correctamente.');
    }

    public function destroy(Request $request, DispositivoIot $dispositivo): RedirectResponse
    {
        $this->verificarCentro($request, $dispositivo);

        if ($dispositivo->sensores()->exists()) {
            return back()->withErrors([
                'delete' => 'No se puede eliminar: este dispositivo todavía tiene sensores asociados.',
            ]);
        }

        $dispositivo->delete();

        return redirect()->route('dispositivos.index')->with('status', 'Dispositivo IoT eliminado.');
    }

    /**
     * Verifica que el usuario (si no es administrador) pertenezca al mismo
     * centro de salud que el dispositivo.
     */
    private function verificarCentro(Request $request, DispositivoIot $dispositivo): void
    {
        $usuario = $request->user();

        if ($usuario->esAdministrador()) {
            return;
        }

        abort_if($dispositivo->centros_salud_id !== $usuario->centros_salud_id, 403, 'No tienes permiso para acceder a este dispositivo.');
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
            'codigo' => ['required', 'string', 'max:30', 'regex:'.TextPatterns::CODIGO, Rule::unique('dispositivos_iot', 'codigo')->ignore($ignorarId)],
            'nombre' => ['required', 'string', 'max:100', 'regex:'.TextPatterns::NOMBRE_EQUIPO],
            'modelo' => ['nullable', 'string', 'max:80', 'regex:'.TextPatterns::MARCA_MODELO_SERIE],
            'tipo_dispositivo' => ['nullable', 'string', 'max:30', 'regex:'.TextPatterns::NOMBRE_EQUIPO],
            'puerto' => ['nullable', 'integer', 'between:1,65535'],
            'direccion_ip' => ['nullable', 'ip'],
            'mac_address' => ['nullable', 'mac_address'],
            'firmware' => ['nullable', 'string', 'max:50', 'regex:'.TextPatterns::CODIGO],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'centros_salud_id' => ['required', 'exists:centros_salud,id'],
        ], [
            'codigo.regex' => 'El código solo puede contener letras, números, guiones y puntos (sin espacios).',
            'nombre.regex' => 'El nombre no puede tener espacios dobles ni caracteres especiales.',
            'modelo.regex' => 'El modelo no puede tener espacios dobles ni caracteres especiales.',
            'tipo_dispositivo.regex' => 'El tipo de dispositivo no puede tener espacios dobles ni caracteres especiales.',
            'puerto.between' => 'El puerto debe ser un número válido entre 1 y 65535.',
            'direccion_ip.ip' => 'Ingresá una dirección IP válida (ej. 192.168.1.10).',
            'mac_address.mac_address' => 'Ingresá una dirección MAC válida (ej. 00:1B:44:11:3A:B7).',
            'firmware.regex' => 'La versión de firmware solo puede contener letras, números, guiones y puntos.',
        ]);
    }
}