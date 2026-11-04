<?php

namespace App\Services;

use App\Models\Bomba;
use App\Models\Sensor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConsumoBomba
{
    public const NIVEL_ENCENDIDO_PCT = 30;
    public const NIVEL_APAGADO_PCT = 90;

    private const DIAS_CALIBRACION = 30;
    private const MIN_CICLOS_CALIBRACION = 3;
    private const DURACION_MINIMA_MIN = 1.0;
    private const TELEMETRIA_MAX_SEGUNDOS = 120;

    private ?array $tiempoCache = null;
    private bool $corrienteConsultada = false;
    private ?Carbon $ultimaLecturaCorrienteCache = null;

    public function __construct(private readonly Bomba $bomba)
    {
    }

    /**
     * Litros estimados entre los niveles de encendido y apagado.
     */
    public function litrosPorCiclo(): ?float
    {
        $capacidad = (float) $this->bomba->tanque_capacidad_litros;

        if ($capacidad <= 0) {
            return null;
        }

        return $capacidad
            * (self::NIVEL_APAGADO_PCT - self::NIVEL_ENCENDIDO_PCT)
            / 100;
    }

    /**
     * Tiempo de llenado configurado o estimado.
     *
     * @return array{minutos: ?float, origen: ?string, ciclos: int}
     */
    public function tiempoLlenado(): array
    {
        if ($this->tiempoCache !== null) {
            return $this->tiempoCache;
        }

        $configurado = (float) $this->bomba->tiempo_llenado_min;

        if ($configurado > 0) {
            return $this->tiempoCache = [
                'minutos' => $configurado,
                'origen' => 'configurado',
                'ciclos' => 0,
            ];
        }

        $desde = now()->subDays(self::DIAS_CALIBRACION);

        $duraciones = $this->intervalos($desde, now())
            ->filter(fn ($i) =>
                ! $i['en_curso']
                && ! $i['telemetria_desactualizada']
                && $i['inicio']->greaterThanOrEqualTo($desde)
            )
            ->map(fn ($i) =>
                ($i['fin']->getTimestamp() - $i['inicio']->getTimestamp()) / 60
            )
            ->filter(fn ($min) => $min >= self::DURACION_MINIMA_MIN)
            ->sort()
            ->values();

        $n = $duraciones->count();

        if ($n < self::MIN_CICLOS_CALIBRACION) {
            return $this->tiempoCache = [
                'minutos' => null,
                'origen' => null,
                'ciclos' => $n,
            ];
        }

        $mitad = intdiv($n, 2);

        $mediana = $n % 2 === 1
            ? $duraciones[$mitad]
            : ($duraciones[$mitad - 1] + $duraciones[$mitad]) / 2;

        return $this->tiempoCache = [
            'minutos' => round($mediana, 1),
            'origen' => 'estimado',
            'ciclos' => $n,
        ];
    }

    /**
     * Obtiene la última lectura de corriente de esta bomba.
     */
    private function ultimaLecturaCorriente(): ?Carbon
    {
        if ($this->corrienteConsultada) {
            return $this->ultimaLecturaCorrienteCache?->copy();
        }

        $this->corrienteConsultada = true;

        $sensor = Sensor::query()
            ->where('bombas_id', $this->bomba->id)
            ->where('tipo', 'corriente')
            ->first();

        if (! $sensor) {
            return null;
        }

        $lectura = $sensor->lecturas()
            ->orderByDesc('fecha_hora')
            ->first();

        if ($lectura && $lectura->fecha_hora) {
            $this->ultimaLecturaCorrienteCache =
                $lectura->fecha_hora->copy();
        }

        return $this->ultimaLecturaCorrienteCache?->copy();
    }

    /**
     * Duración máxima que se acepta como ciclo verificable.
     *
     * Se utiliza el tiempo de llenado configurado más el margen de
     * advertencia. Un intervalo superior se conserva en el historial,
     * pero no genera litros ni tiempo de funcionamiento confirmado.
     */
    private function duracionMaximaVerificableSegundos(): int
    {
        $llenado = (float) $this->bomba->tiempo_llenado_min;

        if ($llenado <= 0) {
            $llenado = self::DURACION_MINIMA_MIN;
        }

        $margen = (int) config(
            'monitoreo.tiempo_advertencia_minutos',
            15
        );

        return (int) ceil(($llenado + max(0, $margen)) * 60);
    }

    /**
     * Caudal estimado en litros por minuto.
     */
    public function caudalLpm(): ?float
    {
        $litros = $this->litrosPorCiclo();
        $tiempo = $this->tiempoLlenado()['minutos'];

        if ($litros === null || $tiempo === null || $tiempo <= 0) {
            return null;
        }

        return $litros / $tiempo;
    }

    /**
     * Obtiene intervalos registrados por los eventos de la bomba.
     *
     * Los intervalos excesivamente largos se conservan en el detalle,
     * pero se marcan como no verificables.
     *
     * Los intervalos abiertos nunca se prolongan después de la última
     * lectura de corriente disponible.
     *
     * @return Collection<int, array{
     *     inicio: Carbon,
     *     fin: Carbon,
     *     en_curso: bool,
     *     telemetria_desactualizada: bool
     * }>
     */
    public function intervalos(Carbon $desde, Carbon $hasta): Collection
    {
        $previo = $this->bomba->eventos()
            ->where('fecha_hora', '<', $desde)
            ->orderByDesc('fecha_hora')
            ->first();

        $eventos = $this->bomba->eventos()
            ->where('fecha_hora', '>=', $desde)
            ->where('fecha_hora', '<=', $hasta)
            ->orderBy('fecha_hora')
            ->get();

        $inicio = ($previo && $previo->encendido)
            ? $previo->fecha_hora->copy()
            : null;

        $intervalos = collect();

        $maxDuracion = $this->duracionMaximaVerificableSegundos();

        foreach ($eventos as $evento) {
            if ($evento->encendido) {
                $inicio ??= $evento->fecha_hora->copy();
                continue;
            }

            if ($inicio === null) {
                continue;
            }

            $fin = $evento->fecha_hora->copy();

            $duracion = max(
                0,
                $fin->getTimestamp() - $inicio->getTimestamp()
            );

            $intervalos->push([
                'inicio' => $inicio->copy(),
                'fin' => $fin,
                'en_curso' => false,
                'telemetria_desactualizada' =>
                    $duracion > $maxDuracion,
            ]);

            $inicio = null;
        }

        if ($inicio !== null) {
            $ahora = now();
            $ultimaLectura = $this->ultimaLecturaCorriente();

            $fin = $ahora->lessThan($hasta)
                ? $ahora->copy()
                : $hasta->copy();

            if ($ultimaLectura === null) {
                $fin = $inicio->copy();
            } elseif ($ultimaLectura->lessThan($fin)) {
                $fin = $ultimaLectura->copy();
            }

            // Una lectura antigua no demuestra que la bomba siga
            // funcionando actualmente.
            $telemetriaDesactualizada = $ultimaLectura === null
                || $ultimaLectura->lt(
                    $ahora->copy()->subSeconds(
                        self::TELEMETRIA_MAX_SEGUNDOS
                    )
                );

            // También se descartan del cálculo los intervalos abiertos
            // anormalmente largos.
            $duracion = max(
                0,
                $fin->getTimestamp() - $inicio->getTimestamp()
            );

            if ($duracion > $maxDuracion) {
                $telemetriaDesactualizada = true;
            }

            $intervalos->push([
                'inicio' => $inicio->copy(),
                'fin' => $fin,
                'en_curso' => true,
                'telemetria_desactualizada' =>
                    $telemetriaDesactualizada,
            ]);
        }

        return $intervalos;
    }

    /**
     * Resume el tiempo y los litros verificables de un período.
     *
     * Los intervalos marcados como no verificables no suman tiempo,
     * ciclos ni litros al resumen.
     */
    public function resumenPeriodo(
        Collection $intervalos,
        Carbon $ini,
        Carbon $fin
    ): array {
        $segundos = 0;
        $ciclos = 0;

        foreach ($intervalos as $i) {
            if ($i['telemetria_desactualizada'] ?? false) {
                continue;
            }

            $a = max(
                $i['inicio']->getTimestamp(),
                $ini->getTimestamp()
            );

            $b = min(
                $i['fin']->getTimestamp(),
                $fin->getTimestamp()
            );

            if ($b > $a) {
                $segundos += $b - $a;
            }

            if (
                $i['inicio']->greaterThanOrEqualTo($ini)
                && $i['inicio']->lessThan($fin)
            ) {
                $ciclos++;
            }
        }

        $caudal = $this->caudalLpm();

        return [
            'segundos' => $segundos,
            'ciclos' => $ciclos,
            'litros' => $caudal !== null
                ? (int) round($segundos / 60 * $caudal)
                : null,
        ];
    }

    /**
     * Resumen de un rango de días, ambos extremos incluidos.
     */
    public function periodo(Carbon $desde, Carbon $hasta): array
    {
        $primerDia = $desde->copy()->startOfDay();
        $ultimoDia = $hasta->copy()->startOfDay();
        $fin = $ultimoDia->copy()->addDay();

        $intervalos = $this->intervalos($primerDia, $fin);

        $nDias = (int) round(
            ($ultimoDia->getTimestamp() - $primerDia->getTimestamp())
            / 86400
        ) + 1;

        $dias = [];

        for ($k = 0; $k < $nDias; $k++) {
            $ini = $ultimoDia->copy()->subDays($k);

            $dias[] = [
                'etiqueta' => ucfirst(
                    $ini->translatedFormat('D d/m/Y')
                ),
            ] + $this->resumenPeriodo(
                $intervalos,
                $ini,
                $ini->copy()->addDay()
            );
        }

        $caudal = $this->caudalLpm();

        $detalle = $intervalos
            ->filter(fn ($i) =>
                $i['fin']->getTimestamp() > $primerDia->getTimestamp()
                && $i['inicio']->lessThan($fin)
            )
            ->map(function ($i) use ($caudal) {
                $minutos = max(
                    0,
                    $i['fin']->getTimestamp()
                    - $i['inicio']->getTimestamp()
                ) / 60;

                $noVerificable =
                    $i['telemetria_desactualizada'] ?? false;

                return [
                    'inicio' => $i['inicio'],
                    'fin' => $i['fin'],
                    'en_curso' => $i['en_curso'],
                    'telemetria_desactualizada' => $noVerificable,
                    'minutos' => round($minutos, 1),
                    'litros' => (
                        $caudal !== null && ! $noVerificable
                    )
                        ? (int) round($minutos * $caudal)
                        : null,
                ];
            })
            ->reverse()
            ->values()
            ->all();

        return [
            'n_dias' => $nDias,
            'total' => $this->resumenPeriodo(
                $intervalos,
                $primerDia,
                $fin
            ),
            'dias' => $dias,
            'detalle' => $detalle,
        ];
    }

    /**
     * Series de los últimos 14 días, 8 semanas y 6 meses.
     */
    public function series(): array
    {
        $ahora = now();

        $primerDia = $ahora->copy()->startOfDay()->subDays(13);
        $primeraSemana = $ahora->copy()->startOfWeek()->subWeeks(7);
        $primerMes = $ahora->copy()
            ->startOfMonth()
            ->subMonthsNoOverflow(5);

        $desde = min($primerDia, $primeraSemana, $primerMes);

        $intervalos = $this->intervalos($desde, $ahora);

        $dias = [];
        $semanas = [];
        $meses = [];

        for ($k = 0; $k < 14; $k++) {
            $ini = $ahora->copy()->startOfDay()->subDays($k);

            $dias[] = [
                'etiqueta' => ucfirst(
                    $ini->translatedFormat('D d/m')
                ),
            ] + $this->resumenPeriodo(
                $intervalos,
                $ini,
                $ini->copy()->addDay()
            );
        }

        for ($k = 0; $k < 8; $k++) {
            $ini = $ahora->copy()->startOfWeek()->subWeeks($k);

            $semanas[] = [
                'etiqueta' => $ini->format('d/m')
                    . ' – '
                    . $ini->copy()->addDays(6)->format('d/m'),
            ] + $this->resumenPeriodo(
                $intervalos,
                $ini,
                $ini->copy()->addWeek()
            );
        }

        for ($k = 0; $k < 6; $k++) {
            $ini = $ahora->copy()
                ->startOfMonth()
                ->subMonthsNoOverflow($k);

            $meses[] = [
                'etiqueta' => ucfirst(
                    $ini->translatedFormat('F Y')
                ),
            ] + $this->resumenPeriodo(
                $intervalos,
                $ini,
                $ini->copy()->addMonth()
            );
        }

        return [
            'dias' => $dias,
            'semanas' => $semanas,
            'meses' => $meses,
            'hoy' => $dias[0],
            'semana' => $semanas[0],
            'mes' => $meses[0],
        ];
    }
}
