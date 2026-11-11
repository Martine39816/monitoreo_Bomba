<?php

namespace App\Services;

use App\Models\Bomba;
use App\Models\EventoBomba;
use App\Models\Lectura;
use App\Models\Sensor;
use Carbon\Carbon;

class NivelTanqueEstimado
{
    public function actualizar(Bomba $bomba): array
    {
        $capacidad = (float) $bomba->tanque_capacidad_litros;

        if ($capacidad <= 0) {
            return $this->resultado(null, null, 'Capacidad no configurada');
        }

        $sensor = Sensor::where('bombas_id', $bomba->id)
            ->where('tipo', 'nivel')
            ->first();

        if (!$sensor) {
            return $this->resultado(null, null, 'Sensor P55 no encontrado');
        }

        $lecturaActual = Lectura::where('sensores_id', $sensor->id)
            ->latest('fecha_hora')
            ->first();

        $lecturaLlena = Lectura::where('sensores_id', $sensor->id)
            ->whereRaw('CAST(valor_medido AS DECIMAL(10,3)) = 0')
            ->latest('fecha_hora')
            ->first();

        $ultimoEvento = EventoBomba::where('bombas_id', $bomba->id)
            ->latest('fecha_hora')
            ->first();

        $ultimoInicio = EventoBomba::where('bombas_id', $bomba->id)
            ->where('encendido', true)
            ->latest('fecha_hora')
            ->first();

        $ultimoApagado = EventoBomba::where('bombas_id', $bomba->id)
            ->where('encendido', false)
            ->latest('fecha_hora')
            ->first();

        $ahora = now();

        $fechaLlena = $lecturaLlena
            ? Carbon::parse($lecturaLlena->fecha_hora)
            : null;

        $fechaInicio = $ultimoInicio
            ? Carbon::parse($ultimoInicio->fecha_hora)
            : null;

        $fechaApagado = $ultimoApagado
            ? Carbon::parse($ultimoApagado->fecha_hora)
            : null;

        $fechaLlenaGuardada = $bomba->nivel_tanque_ultimo_lleno_en
            ? Carbon::parse($bomba->nivel_tanque_ultimo_lleno_en)
            : null;

        $fechaInicioGuardada = $bomba->nivel_tanque_ultimo_inicio_en
            ? Carbon::parse($bomba->nivel_tanque_ultimo_inicio_en)
            : null;

        $nivel = $bomba->nivel_tanque_estimado_pct !== null
            ? (float) $bomba->nivel_tanque_estimado_pct
            : null;

        $caudal = (new ConsumoBomba($bomba))->caudalLpm();

        $consumo = $bomba->nivel_tanque_consumo_lpm !== null
            ? (float) $bomba->nivel_tanque_consumo_lpm
            : null;

        /*
         * 1. Guardar el último registro de tanque lleno.
         * El P55 activado se representa con 0.000.
         */
        if ($fechaLlena && (
            !$fechaLlenaGuardada ||
            $fechaLlena->gt($fechaLlenaGuardada)
        )) {
            $bomba->nivel_tanque_ultimo_lleno_en = $fechaLlena;
            $fechaLlenaGuardada = $fechaLlena;
        }

        /*
         * 2. Cuando la bomba arranca, el flotador eléctrico
         * indica que se alcanzó aproximadamente el 30%.
         */
         if (
                $bomba->funcionando &&
                $fechaInicio &&
                (
                    !$fechaInicioGuardada ||
                    $fechaInicio->gt($fechaInicioGuardada)
                )
            ) {
            if (
                $fechaLlenaGuardada &&
                $fechaInicio->gt($fechaLlenaGuardada)
            ) {
                $minutosConsumo = $fechaLlenaGuardada
                    ->diffInSeconds($fechaInicio) / 60;

                // Solo calibrar con intervalos de hasta 7 días.
                if ($minutosConsumo > 0 && $minutosConsumo <= 10080) {
                    $consumo = ($capacidad * 0.60) / $minutosConsumo;

                    $bomba->nivel_tanque_consumo_lpm = $consumo;
                }
            }

            $nivel = 30;
            $bomba->nivel_tanque_ultimo_inicio_en = $fechaInicio;

            $fechaInicioGuardada = $fechaInicio;
        }

        /*
         * 3. Si el P55 está actualmente activado y la lectura
         * es reciente, confirmar el nivel superior del 90%.
         */
        $p55LlenoActual = false;

        if ($lecturaActual) {
            $fechaLecturaActual = Carbon::parse($lecturaActual->fecha_hora);

            $antiguedad = $fechaLecturaActual->diffInSeconds($ahora);

            $p55LlenoActual =
                (float) $lecturaActual->valor_medido === 0.0 &&
                $antiguedad <= 120;
        }

        if ($p55LlenoActual) {
            $nivel = 90;

            if (!$fechaLlenaGuardada ||
                !$fechaLlenaGuardada->equalTo($fechaLecturaActual)) {
                $bomba->nivel_tanque_ultimo_lleno_en = $fechaLecturaActual;
            }
        }

        /*
         * 4. Calcular el incremento durante el funcionamiento real.
         */
        $eventoIndicaEncendida = $ultimoEvento
            ? (bool) $ultimoEvento->encendido
            : false;

        if (
            !$p55LlenoActual &&
            $eventoIndicaEncendida &&
            $bomba->funcionando &&
            $fechaInicio &&
            $caudal !== null &&
            $caudal > 0 &&
            (!$fechaLlenaGuardada || $fechaInicio->gt($fechaLlenaGuardada))
        ) {
            $minutosFuncionamiento = max(
                0,
                $fechaInicio->diffInSeconds($ahora) / 60
            );

            $litrosIngresados = $caudal * $minutosFuncionamiento;

            $nivel = min(
                90,
                30 + ($litrosIngresados / $capacidad * 100)
            );
        }

        /*
         * 5. Si la bomba se apagó antes de alcanzar el P55,
         * conservar el nivel calculado al detenerse y estimar
         * el descenso según el consumo histórico.
         */
        if (
            !$p55LlenoActual &&
            !$eventoIndicaEncendida &&
            $fechaInicio &&
            $fechaApagado &&
            $fechaApagado->gt($fechaInicio) &&
            (!$fechaLlenaGuardada || $fechaInicio->gt($fechaLlenaGuardada)) &&
            $caudal !== null &&
            $caudal > 0
        ) {
            $minutosLlenado = $fechaInicio->diffInSeconds($fechaApagado) / 60;

            $nivelAlApagarse = min(
                90,
                30 + (($caudal * $minutosLlenado) / $capacidad * 100)
            );

            if ($consumo !== null && $consumo > 0) {
                $minutosApagada = $fechaApagado->diffInSeconds($ahora) / 60;

                $nivel = max(
                    0,
                    $nivelAlApagarse -
                    (($consumo * $minutosApagada) / $capacidad * 100)
                );
            } else {
                $nivel = $nivelAlApagarse;
            }
        }

        /*
         * 6. Si el último llenado confirmado fue posterior
         * al último arranque, estimar el descenso desde el 90%.
         */
        if (
            !$p55LlenoActual &&
            $fechaLlenaGuardada &&
            (!$fechaInicio || $fechaLlenaGuardada->gt($fechaInicio)) &&
            $consumo !== null &&
            $consumo > 0 &&
            !$bomba->funcionando
        ) {
            $minutosConsumo = $fechaLlenaGuardada->diffInSeconds($ahora) / 60;

            $nivel = max(
                0,
                90 - (($consumo * $minutosConsumo) / $capacidad * 100)
            );
        }

        if ($nivel !== null) {
            $nivel = round(max(0, min(90, $nivel)), 2);
            $bomba->nivel_tanque_estimado_pct = $nivel;
        }

        $bomba->save();

        $litros = $nivel !== null
            ? round($capacidad * $nivel / 100, 2)
            : null;

        return $this->resultado(
            $nivel,
            $litros,
            $p55LlenoActual
                ? 'Nivel superior confirmado por P55'
                : ($nivel === null ? 'Sin datos suficientes' : 'Nivel estimado')
        );
    }

    private function resultado(
        ?float $porcentaje,
        ?float $litros,
        string $estado
    ): array {
        return [
            'porcentaje' => $porcentaje,
            'litros' => $litros,
            'estado' => $estado,
        ];
    }
}