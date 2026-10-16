<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
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

        $query = Lectura::with('sensor.bomba')->orderBy('fecha_hora', 'desc');

        if ($request->filled('sensor_id')) {
            $query->where('sensores_id', $request->input('sensor_id'));
        }

        if (! $usuario->esAdministrador()) {
            $query->whereHas('sensor.bomba', function ($q) use ($usuario) {
                $q->where('centros_salud_id', $usuario->centros_salud_id);
            });
        }

        $lecturas = $query->paginate(30)->withQueryString();
        $sensores = Sensor::orderBy('nombre')->get();

        // Tarjetas resumen: última lectura conocida de cada sensor
        $resumenSensores = Sensor::with(['ultimaLectura', 'bomba'])
            ->when(! $usuario->esAdministrador(), function ($q) use ($usuario) {
                $q->whereHas('bomba', function ($qb) use ($usuario) {
                    $qb->where('centros_salud_id', $usuario->centros_salud_id);
                });
            })
            ->orderBy('nombre')
            ->get();

        // Datos para el gráfico: últimas 50 lecturas del sensor filtrado, en orden cronológico
        $sensorGrafico = null;
        $datosGrafico = [];

        if ($request->filled('sensor_id')) {
            $sensorGrafico = Sensor::find($request->input('sensor_id'));

            if ($sensorGrafico) {
                $datosGrafico = Lectura::where('sensores_id', $sensorGrafico->id)
                    ->orderBy('fecha_hora', 'desc')
                    ->limit(50)
                    ->get()
                    ->sortBy('fecha_hora')
                    ->map(fn ($l) => [
                        'hora' => $l->fecha_hora->format('d/m H:i'),
                        'valor' => (float) $l->valor_medido,
                    ])
                ->values();
            }
        }

        return view('lecturas.index', compact(
            'lecturas', 'sensores', 'resumenSensores', 'datosGrafico', 'sensorGrafico'
        ));
    }

    /**
     * Formulario para registrar una lectura de prueba (simula lo que haría el ESP32).
     */
    public function create(): View
    {
        $sensores = Sensor::with('bomba')->orderBy('nombre')->get();

        return view('lecturas.create', compact('sensores'));
    }

    /**
     * Guarda una lectura de prueba y, si el valor está fuera de rango, genera una alerta.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sensores_id' => ['required', 'exists:sensores,id'],
            'valor_medido' => ['required', 'numeric', 'gte:0'],
            'fecha_hora' => ['nullable', 'date'],
        ]);

        $sensor = Sensor::with('bomba')->findOrFail($validated['sensores_id']);

        // Sensores que solo tienen sentido con la bomba encendida.
        $tiposDependientesDeBomba = ['corriente', 'vibracion'];

        if (in_array($sensor->tipo, $tiposDependientesDeBomba, true) && ! ($sensor->bomba?->encendido ?? false)) {
            return back()->withErrors([
                'valor_medido' => 'La bomba está apagada. No se pueden registrar lecturas de ' . $sensor->tipo . '.',
            ])->withInput();
        }

        $lectura = Lectura::create([
            'sensores_id' => $validated['sensores_id'],
            'valor_medido' => $validated['valor_medido'],
            'fecha_hora' => $validated['fecha_hora'] ?? now(),
        ]);

        $alertaGenerada = $this->evaluarYGenerarAlerta($lectura, $sensor);

        $mensaje = 'Lectura registrada correctamente.';
        if ($alertaGenerada) {
            $mensaje .= ' Se generó una alerta porque el valor está fuera del rango normal.';
        }

        return redirect()->route('lecturas.index')->with('status', $mensaje);
    }

    /**
     * Evalúa si la lectura está fuera del rango permitido y genera una alerta
     * (misma lógica que usa la API de dispositivos IoT).
     */
    private function evaluarYGenerarAlerta(Lectura $lectura, Sensor $sensor): bool
    {
        if ($sensor->valor_minimo === null || $sensor->valor_maximo === null) {
            return false;
        }

        $valor = (float) $lectura->valor_medido;
        $fueraDeRango = null;

        if ($valor > $sensor->valor_maximo) {
            $fueraDeRango = 'alto';
        } elseif ($valor < $sensor->valor_minimo) {
            $fueraDeRango = 'bajo';
        }

        if (! $fueraDeRango) {
            return false;
        }

        $tipoAlerta = $this->mapearTipoAlerta($sensor->tipo, $fueraDeRango);

        $yaExistePendiente = Alerta::where('sensores_id', $sensor->id)
            ->where('tipo', $tipoAlerta)
            ->where('atendida', false)
            ->exists();

        if ($yaExistePendiente) {
            return false;
        }

        Alerta::create([
            'tipo' => $tipoAlerta,
            'descripcion' => sprintf(
                'Valor %s detectado: %s %s (rango normal: %s a %s %s)',
                $fueraDeRango === 'alto' ? 'alto' : 'bajo',
                $valor,
                $sensor->unidad_medida,
                $sensor->valor_minimo,
                $sensor->valor_maximo,
                $sensor->unidad_medida
            ),
            'valor_detectado' => $valor,
            'valor_permitido' => $fueraDeRango === 'alto' ? $sensor->valor_maximo : $sensor->valor_minimo,
            'fecha_hora' => $lectura->fecha_hora,
            'atendida' => false,
            'sensores_id' => $sensor->id,
            'bombas_id' => $sensor->bombas_id,
        ]);

        return true;
    }

    private function mapearTipoAlerta(string $tipoSensor, string $direccion): string
    {
        return match (true) {
            $tipoSensor === 'nivel' && $direccion === 'alto' => 'nivel_alto',
            $tipoSensor === 'nivel' && $direccion === 'bajo' => 'nivel_bajo',
            $tipoSensor === 'temperatura' && $direccion === 'alto' => 'temperatura_alta',
            $tipoSensor === 'corriente' && $direccion === 'alto' => 'sobrecorriente',
            $tipoSensor === 'vibracion' && $direccion === 'alto' => 'vibracion_alta',
            default => 'falla_electrica',
        };
    }
}
