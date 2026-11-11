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

        // Administrador y Técnico ven todos los centros; el Director solo el suyo.
        $query = Lectura::visiblesPara($usuario)->with('sensor.bomba')->orderBy('fecha_hora', 'desc');

        if ($request->filled('sensor_id')) {
            $query->where('sensores_id', $request->input('sensor_id'));
        }

        $lecturas = $query->paginate(30)->withQueryString();
        $sensores = Sensor::visiblesPara($usuario)->orderBy('nombre')->get();

        // Tarjetas resumen: última lectura conocida de cada sensor
        $resumenSensores = Sensor::visiblesPara($usuario)
            ->with(['ultimaLectura', 'bomba'])
            ->orderBy('nombre')
            ->get();

        // Datos para el gráfico: últimas 50 lecturas del sensor filtrado, en orden cronológico
        $sensorGrafico = null;
        $datosGrafico = [];

        if ($request->filled('sensor_id')) {
            // Si el sensor es de otro centro (director), no se muestra.
            $sensorGrafico = Sensor::visiblesPara($usuario)->find($request->input('sensor_id'));

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
}
