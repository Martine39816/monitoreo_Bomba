<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Bomba;
use App\Models\Lectura;
use App\Models\Sensor;

class NivelUltrasonicoTanque
{
    public function procesar(Sensor $sensor, Lectura $lectura): void
    {
        $modelo = strtolower((string) $sensor->modelo);
        $unidad = strtolower(trim((string) $sensor->unidad_medida));
        $esUltrasonico = $unidad === 'cm'
            || str_contains($modelo, 'hc-sr04')
            || str_contains($modelo, 'ultrasonico');
        if (! $esUltrasonico || $sensor->tipo !== 'nivel') return;

        $bomba = Bomba::find($sensor->bombas_id);
        if (! $bomba || (float) $bomba->tanque_capacidad_litros <= 0) return;

        $distancia = (float) $lectura->valor_medido;
        $lleno = (float) config('monitoreo.ultrasonico.distancia_lleno_cm', 2);
        $vacio = (float) config('monitoreo.ultrasonico.distancia_vacio_cm', 52);
        if ($vacio <= $lleno || $distancia < $lleno || $distancia > $vacio) return;

        // Altura relativa del agua (0 a 1) entre los puntos calibrados.
        $fraccion = ($vacio - $distancia) / ($vacio - $lleno);
        $fraccion = max(0, min(1, $fraccion));
        $capacidad = (float) $bomba->tanque_capacidad_litros;
        $litros = $capacidad * $fraccion;
        $porcentaje = $fraccion * 100;

        $anterior = $sensor->lecturas()
            ->where('id', '<>', $lectura->id)
            ->where('fecha_hora', '>=', $lectura->fecha_hora->copy()->subMinutes(
                max(1, (int) config('monitoreo.ultrasonico.ventana_alerta_minutos', 10))
            ))
            ->where('fecha_hora', '<', $lectura->fecha_hora)
            ->orderByDesc('fecha_hora')->first();

        // El campo existente guarda el último nivel calculado; no se crea columna.
        $bomba->update(['nivel_tanque_estimado_pct' => round($porcentaje, 2)]);

        if (! $anterior) return;

        $distanciaAnterior = (float) $anterior->valor_medido;
        if ($distanciaAnterior < $lleno || $distanciaAnterior > $vacio) return;
        $fraccionAnterior = max(0, min(1, ($vacio - $distanciaAnterior) / ($vacio - $lleno)));
        $litrosAnteriores = $capacidad * $fraccionAnterior;
        $caidaLitros = $litrosAnteriores - $litros;
        $caidaMinima = $capacidad * ((float) config('monitoreo.ultrasonico.caida_alerta_pct', 10) / 100);

        // Si la bomba está llenando, la variación neta no permite estimar consumo.
        // Se evita generar una falsa alarma mientras funciona.
        if ($bomba->funcionando || $caidaLitros < $caidaMinima) return;

        $pendiente = Alerta::where('bombas_id', $bomba->id)
            ->where('sensores_id', $sensor->id)
            ->where('tipo', 'consumo_excesivo')->where('atendida', false)->exists();
        if ($pendiente) return;

        Alerta::create([
            'tipo' => 'consumo_excesivo',
            'descripcion' => sprintf(
                'El nivel del tanque disminuyó aproximadamente %.1f litros en el intervalo de medición. Posible consumo elevado o fuga. Verificar el tanque y las tuberías.',
                $caidaLitros
            ),
            'valor_detectado' => round($caidaLitros, 3),
            'valor_permitido' => round($caidaMinima, 3),
            'fecha_hora' => $lectura->fecha_hora,
            'atendida' => false,
            'sensores_id' => $sensor->id,
            'bombas_id' => $bomba->id,
        ]);
    }
}
