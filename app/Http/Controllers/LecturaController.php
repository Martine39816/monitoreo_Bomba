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
        $query = Lectura::with('sensor.bomba')->orderBy('fecha_hora', 'desc');

        if ($request->filled('sensor_id')) {
            $query->where('sensores_id', $request->input('sensor_id'));
        }

        $lecturas = $query->paginate(30)->withQueryString();
        $sensores = Sensor::orderBy('nombre')->get();

        return view('lecturas.index', compact('lecturas', 'sensores'));
    }

    public function create(): View
    {
        $sensores = Sensor::with('bomba')->orderBy('nombre')->get();

        return view('lecturas.create', compact('sensores'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sensores_id' => ['required', 'exists:sensores,id'],
            'valor_medido' => ['required', 'numeric', 'gte:0'],
            'fecha_hora' => ['nullable', 'date'],
        ]);

        $lectura = Lectura::create([
            'sensores_id' => $validated['sensores_id'],
            'valor_medido' => $validated['valor_medido'],
            'fecha_hora' => $validated['fecha_hora'] ?? now(),
        ]);

        $alertaGenerada = $this->evaluarYGenerarAlerta($lectura);

        $mensaje = 'Lectura registrada correctamente.';
        if ($alertaGenerada) {
            $mensaje .= ' ⚠️ Se generó una alerta automática porque el valor está fuera de rango.';
        }

        return redirect()->route('lecturas.index')->with('status', $mensaje);
    }

    /**
     * Compara la lectura contra el rango normal de su sensor.
     * Si esta fuera de rango, crea una alerta automatica (si no existe ya una pendiente igual).
     */
    private function evaluarYGenerarAlerta(Lectura $lectura): bool
    {
        $sensor = $lectura->sensor;

        if (! $sensor || $sensor->valor_minimo === null || $sensor->valor_maximo === null) {
            return false; // sin rango configurado, no se puede evaluar
        }

        $valor = (float) $lectura->valor_medido;
        $fueraDeRango = null; // 'alto' | 'bajo' | null

        if ($valor > $sensor->valor_maximo) {
            $fueraDeRango = 'alto';
        } elseif ($valor < $sensor->valor_minimo) {
            $fueraDeRango = 'bajo';
        }

        if (! $fueraDeRango) {
            return false; // dentro de rango, todo normal
        }

        $tipoAlerta = $this->mapearTipoAlerta($sensor->tipo, $fueraDeRango);

        // Evita duplicar alertas: si ya hay una pendiente del mismo sensor y tipo, no crea otra
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
                'Valor %s detectado automáticamente: %s %s (rango normal: %s a %s %s)',
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
            default => 'falla_electrica', // fallback para combinaciones sin tipo especifico
        };
    }
}