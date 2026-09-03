<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\Lectura;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispositivoApiController extends Controller
{
    /**
     * POST /api/lecturas
     * El ESP32 envia una lectura individual de un sensor.
     */
    public function guardarLectura(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sensores_id' => ['required', 'exists:sensores,id'],
            'valor_medido' => ['required', 'numeric', 'gte:0'],
        ]);

        // Verifica que el sensor pertenezca a un dispositivo autenticado
        // (evita que un dispositivo mande datos de sensores que no son suyos)
        $dispositivo = $request->attributes->get('dispositivo');
        $sensor = Sensor::findOrFail($validated['sensores_id']);

        if ($sensor->dispositivos_iot_id !== $dispositivo->id) {
            return response()->json(['message' => 'Este sensor no pertenece a tu dispositivo.'], 403);
        }

        $lectura = Lectura::create([
            'sensores_id' => $validated['sensores_id'],
            'valor_medido' => $validated['valor_medido'],
            'fecha_hora' => now(),
        ]);

        $alertaGenerada = $this->evaluarYGenerarAlerta($lectura, $sensor);

        return response()->json([
            'guardado' => true,
            'alerta_generada' => $alertaGenerada,
        ], 201);
    }

    /**
     * GET /api/bombas/{id}/estado
     * El ESP32 consulta si debe encender/apagar segun lo que diga el panel web.
     */
    public function estadoBomba(Request $request, int $id): JsonResponse
    {
        $dispositivo = $request->attributes->get('dispositivo');

        $bomba = Bomba::where('id', $id)
            ->where('centros_salud_id', $dispositivo->centros_salud_id)
            ->firstOrFail();

        return response()->json([
            'encendido' => (bool) $bomba->encendido,
            'modo_operacion' => (int) $bomba->modo_operacion, // 0 = manual, 1 = automatico
        ]);
    }

    /**
     * Misma logica automatica de alertas que ya usa LecturaController (web).
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
                'Valor %s detectado automaticamente (IoT): %s %s (rango normal: %s a %s %s)',
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