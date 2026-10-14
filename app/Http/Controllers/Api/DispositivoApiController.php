<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\Lectura;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        $dispositivo = $request->attributes->get('dispositivo');
        $sensor = Sensor::with('bomba')->findOrFail($validated['sensores_id']);

        if ($sensor->dispositivos_iot_id !== $dispositivo->id) {
            return response()->json(['message' => 'Este sensor no pertenece a tu dispositivo.'], 403);
        }

        // Se anota que el sensor "dio senales de vida", incluso si la lectura
        // se descarta mas abajo. Corriente y vibracion no dejan filas en la base
        // con la bomba detenida, asi que sin este registro no se podria saber
        // si un sensor esta desconectado o simplemente la bomba esta apagada.
        $this->registrarSensorVisto($sensor);

        $bomba = $sensor->bomba;
        $cambioDeEstado = false;

        // La corriente es la fuente de verdad de si la bomba esta funcionando.
        if ($sensor->tipo === 'corriente' && $bomba) {
            $cambioDeEstado = $this->actualizarEstadoFuncionamiento($bomba, (float) $validated['valor_medido']);
        }

        // Corriente y vibracion no aportan datos con la bomba detenida:
        // se descartan, salvo la lectura que justo marco un cambio de estado.
        if (in_array($sensor->tipo, ['corriente', 'vibracion'], true)
            && $bomba && ! $bomba->funcionando && ! $cambioDeEstado) {
            return response()->json([
                'guardado' => false,
                'motivo' => 'Bomba detenida: lectura de '.$sensor->tipo.' descartada.',
            ], 200);
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
            'funcionando' => (bool) $bomba->funcionando,
            'modo_operacion' => (int) $bomba->modo_operacion, // 0 = manual, 1 = automatico
        ]);
    }

    /**
     * Guarda la marca de tiempo de la ultima lectura recibida de un sensor.
     * Usa la cache de archivos para no depender de la tabla 'cache' ni de
     * cambios en la base de datos.
     */
    private function registrarSensorVisto(Sensor $sensor): void
    {
        Cache::store('file')->put(
            'sensor_visto_'.$sensor->id,
            now()->timestamp,
            now()->addDay()
        );
    }

    /**
     * Decide si la bomba esta funcionando segun la corriente medida.
     * Un cambio de estado solo se acepta tras varias lecturas consecutivas
     * que lo confirmen, para que un pico de ruido aislado no lo provoque.
     * Devuelve true si el estado cambio con esta lectura.
     */
    private function actualizarEstadoFuncionamiento(Bomba $bomba, float $corriente): bool
    {
        $umbralOn = (float) config('monitoreo.corriente_umbral_encendido');
        $umbralOff = (float) config('monitoreo.corriente_umbral_apagado');
        $confirmaciones = max(1, (int) config('monitoreo.corriente_lecturas_confirmacion'));
        $ventana = max(10, (int) config('monitoreo.corriente_confirmacion_ventana_segundos'));

        // Estado que sugiere esta lectura (null = confirma el estado actual).
        $sugerido = null;

        if (! $bomba->funcionando && $corriente >= $umbralOn) {
            $sugerido = true;
        } elseif ($bomba->funcionando && $corriente < $umbralOff) {
            $sugerido = false;
        }

        $cache = Cache::store('file');
        $clave = 'bomba_'.$bomba->id.'_confirmacion_estado';

        if ($sugerido === null) {
            $cache->forget($clave); // una lectura normal rompe la racha
            return false;
        }

        $cuenta = (int) $cache->get($clave, 0) + 1;

        if ($cuenta < $confirmaciones) {
            $cache->put($clave, $cuenta, now()->addSeconds($ventana));
            return false;
        }

        $cache->forget($clave);
        $bomba->update(['funcionando' => $sugerido]); // el Observer registra el evento

        return true;
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
