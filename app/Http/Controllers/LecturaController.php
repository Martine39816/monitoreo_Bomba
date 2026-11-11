<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Sensor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LecturaController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $query = Lectura::visiblesPara($usuario)
            ->with('sensor.bomba')
            ->orderByDesc('fecha_hora');

        if ($request->filled('sensor_id')) {
            $query->where('sensores_id', $request->integer('sensor_id'));
        }

        $lecturas = $query->paginate(30)->withQueryString();
        $sensores = Sensor::visiblesPara($usuario)->orderBy('nombre')->get();
        $resumenSensores = Sensor::visiblesPara($usuario)
            ->with(['ultimaLectura', 'bomba'])
            ->orderBy('nombre')->get();

        $sensorGrafico = null;
        $datosGrafico = [];

        if ($request->filled('sensor_id')) {
            $sensorGrafico = Sensor::visiblesPara($usuario)->find($request->integer('sensor_id'));
            if ($sensorGrafico) {
                $datosGrafico = Lectura::where('sensores_id', $sensorGrafico->id)
                    ->orderByDesc('fecha_hora')->limit(50)->get()
                    ->sortBy('fecha_hora')
                    ->map(fn ($l) => [
                        'hora' => $l->fecha_hora->format('d/m H:i'),
                        'valor' => (float) $l->valor_medido,
                    ])->values();
            }
        }

        return view('lecturas.index', compact(
            'lecturas', 'sensores', 'resumenSensores', 'datosGrafico', 'sensorGrafico'
        ));
    }

    // Formulario de simulación. Solo administrador y técnico llegan por la ruta protegida.
    public function create(Request $request): View
    {
        $sensores = Sensor::visiblesPara($request->user())
            ->with('bomba')->where('estado', 'activo')
            ->orderBy('nombre')->get();

        return view('lecturas.create', compact('sensores'));
    }

    // Guarda una lectura de prueba en la misma tabla usada por el ESP32.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'sensores_id' => ['required', 'integer', 'exists:sensores,id'],
            'valor_medido' => ['required', 'numeric', 'between:0,999999'],
            'fecha_hora' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $sensor = Sensor::visiblesPara($request->user())
            ->findOrFail($datos['sensores_id']);

        Lectura::create([
            'sensores_id' => $sensor->id,
            'valor_medido' => $datos['valor_medido'],
            'fecha_hora' => $datos['fecha_hora'] ?? now(),
        ]);

        return redirect()->route('lecturas.index', ['sensor_id' => $sensor->id])
            ->with('status', 'Lectura de prueba registrada correctamente.');
    }
}
