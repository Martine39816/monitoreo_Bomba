<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Lectura;
use App\Models\Sensor;

/**
 * Punto unico de verdad para la generacion automatica de alertas por umbral.
 *
 * Tanto el registro de lecturas desde la web (LecturaController) como la API
 * de dispositivos IoT (DispositivoApiController) deben usar esta misma
 * implementacion para que las reglas de evaluacion y el mapeo de tipos de
 * alerta no diverjan entre las dos vias.
 */
class AlertaService
{
    /**
     * Marca de origen para las alertas generadas por la API de dispositivos.
     * Se intercala en la descripcion sin duplicar la logica de negocio.
     */
    public const SUFIJO_IOT = ' automaticamente (IoT)';

    /**
     * Evalua la lectura contra los valores minimo y maximo configurados en el
     * sensor y, si el valor esta fuera de rango, registra una alerta pendiente.
     *
     * Devuelve true unicamente cuando se crea una alerta nueva. No genera nada
     * si los umbrales no estan configurados, si el valor esta dentro de rango o
     * si ya existe una alerta pendiente del mismo sensor y tipo (deduplicacion
     * secuencial que ya aplicaban los flujos web e IoT).
     *
     * @param  string  $sufijoDescripcion  Texto de origen intercalado en la descripcion.
     */
    public function evaluarYGenerarAlerta(Lectura $lectura, Sensor $sensor, string $sufijoDescripcion = ''): bool
    {
        if ($sensor->valor_minimo === null || $sensor->valor_maximo === null) {
            return false;
        }

        $valor = (float) $lectura->valor_medido;
        $fueraDeRango = $this->determinarDireccion($valor, $sensor);

        if ($fueraDeRango === null) {
            return false;
        }

        $tipoAlerta = $this->mapearTipoAlerta($sensor->tipo, $fueraDeRango);

        if ($this->existeAlertaPendiente((int) $sensor->id, $tipoAlerta)) {
            return false;
        }

        Alerta::create([
            'tipo' => $tipoAlerta,
            'descripcion' => sprintf(
                'Valor %s detectado%s: %s %s (rango normal: %s a %s %s)',
                $fueraDeRango,
                $sufijoDescripcion,
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

    /**
     * Determina la direccion del valor respecto a los umbrales del sensor.
     *
     * @return 'alto'|'bajo'|null
     */
    private function determinarDireccion(float $valor, Sensor $sensor): ?string
    {
        if ($valor > $sensor->valor_maximo) {
            return 'alto';
        }

        if ($valor < $sensor->valor_minimo) {
            return 'bajo';
        }

        return null;
    }

    /**
     * Indica si ya existe una alerta pendiente para el mismo sensor y tipo.
     */
    private function existeAlertaPendiente(int $sensoresId, string $tipoAlerta): bool
    {
        return Alerta::where('sensores_id', $sensoresId)
            ->where('tipo', $tipoAlerta)
            ->where('atendida', false)
            ->exists();
    }

    /**
     * Traduce el tipo de sensor y la direccion a uno de los tipos de alerta
     * existentes en el esquema.
     */
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
