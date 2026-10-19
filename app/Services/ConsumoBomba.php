<?php

namespace App\Services;

use App\Models\Bomba;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Estima el agua bombeada a partir de los ciclos de funcionamiento que ya
 * registra `eventos_bomba` (BombaObserver, según la corriente medida).
 * No crea ni guarda nada: todo se calcula al consultar.
 *
 *   litros del ciclo completo = capacidad del tanque × (90 % − 30 %)
 *   caudal medio (L/min)      = litros del ciclo ÷ tiempo de llenado (min)
 *   litros de un período      = minutos funcionando × caudal medio
 *
 * Es agua BOMBEADA estimada. Equivale al consumo del centro solo si el tanque
 * empieza y termina el período al mismo nivel.
 */
class ConsumoBomba
{
    public const NIVEL_ENCENDIDO_PCT = 30;
    public const NIVEL_APAGADO_PCT = 90;

    private const DIAS_CALIBRACION = 30;
    private const MIN_CICLOS_CALIBRACION = 3;
    private const DURACION_MINIMA_MIN = 1.0; // ignora encendidos de ruido

    private ?array $tiempoCache = null;

    public function __construct(private readonly Bomba $bomba)
    {
    }

    /** Litros que entran en un ciclo completo (30 % → 90 %), o null si no hay capacidad. */
    public function litrosPorCiclo(): ?float
    {
        $capacidad = (float) $this->bomba->tanque_capacidad_litros;

        if ($capacidad <= 0) {
            return null;
        }

        return $capacidad * (self::NIVEL_APAGADO_PCT - self::NIVEL_ENCENDIDO_PCT) / 100;
    }

    /**
     * Minutos que tarda un llenado completo.
     * 'configurado' = lo cargó una persona; 'estimado' = mediana de los ciclos
     * reales de los últimos 30 días (necesita al menos 3).
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
            return $this->tiempoCache = ['minutos' => $configurado, 'origen' => 'configurado', 'ciclos' => 0];
        }

        $desde = now()->subDays(self::DIAS_CALIBRACION);

        $duraciones = $this->intervalos($desde, now())
            ->filter(fn ($i) => ! $i['en_curso'] && $i['inicio']->greaterThanOrEqualTo($desde))
            ->map(fn ($i) => ($i['fin']->getTimestamp() - $i['inicio']->getTimestamp()) / 60)
            ->filter(fn ($min) => $min >= self::DURACION_MINIMA_MIN)
            ->sort()
            ->values();

        $n = $duraciones->count();

        if ($n < self::MIN_CICLOS_CALIBRACION) {
            return $this->tiempoCache = ['minutos' => null, 'origen' => null, 'ciclos' => $n];
        }

        $mitad = intdiv($n, 2);
        $mediana = $n % 2 === 1
            ? $duraciones[$mitad]
            : ($duraciones[$mitad - 1] + $duraciones[$mitad]) / 2;

        return $this->tiempoCache = ['minutos' => round($mediana, 1), 'origen' => 'estimado', 'ciclos' => $n];
    }

    /** Caudal medio estimado en litros por minuto, o null si faltan datos. */
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
     * Periodos de funcionamiento (encendido → apagado) entre $desde y $hasta.
     * Si la bomba ya estaba funcionando al llegar a $desde, el ciclo se cuenta
     * desde su inicio real. Un ciclo sin apagado se cierra "ahora" (en_curso).
     *
     * @return Collection<int, array{inicio: Carbon, fin: Carbon, en_curso: bool}>
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

        $inicio = ($previo && $previo->encendido) ? $previo->fecha_hora->copy() : null;
        $intervalos = collect();

        foreach ($eventos as $evento) {
            if ($evento->encendido) {
                $inicio ??= $evento->fecha_hora->copy();
            } elseif ($inicio !== null) {
                $intervalos->push(['inicio' => $inicio, 'fin' => $evento->fecha_hora->copy(), 'en_curso' => false]);
                $inicio = null;
            }
        }

        if ($inicio !== null) {
            $fin = now()->lessThan($hasta) ? now() : $hasta->copy();
            $intervalos->push(['inicio' => $inicio, 'fin' => $fin, 'en_curso' => true]);
        }

        return $intervalos;
    }

    /** Ciclos, tiempo funcionando y litros estimados dentro de [$ini, $fin). */
    public function resumenPeriodo(Collection $intervalos, Carbon $ini, Carbon $fin): array
    {
        $segundos = 0;
        $ciclos = 0;

        foreach ($intervalos as $i) {
            $a = max($i['inicio']->getTimestamp(), $ini->getTimestamp());
            $b = min($i['fin']->getTimestamp(), $fin->getTimestamp());

            if ($b > $a) {
                $segundos += $b - $a;
            }

            if ($i['inicio']->greaterThanOrEqualTo($ini) && $i['inicio']->lessThan($fin)) {
                $ciclos++;
            }
        }

        $caudal = $this->caudalLpm();

        return [
            'segundos' => $segundos,
            'ciclos' => $ciclos,
            'litros' => $caudal !== null ? round($segundos / 60 * $caudal) : null,
        ];
    }

    /**
     * Resumen de un rango de días (ambos extremos incluidos): total, desglose
     * diario (el día más reciente primero) y detalle de cada ciclo.
     */
    public function periodo(Carbon $desde, Carbon $hasta): array
    {
        $primerDia = $desde->copy()->startOfDay();
        $ultimoDia = $hasta->copy()->startOfDay();
        $fin = $ultimoDia->copy()->addDay();

        $intervalos = $this->intervalos($primerDia, $fin);

        $nDias = (int) round(($ultimoDia->getTimestamp() - $primerDia->getTimestamp()) / 86400) + 1;

        $dias = [];
        for ($k = 0; $k < $nDias; $k++) {
            $ini = $ultimoDia->copy()->subDays($k);
            $dias[] = ['etiqueta' => ucfirst($ini->translatedFormat('D d/m/Y'))]
                + $this->resumenPeriodo($intervalos, $ini, $ini->copy()->addDay());
        }

        $caudal = $this->caudalLpm();

        $detalle = $intervalos
            ->filter(fn ($i) => $i['fin']->getTimestamp() > $primerDia->getTimestamp() && $i['inicio']->lessThan($fin))
            ->map(function ($i) use ($caudal) {
                $min = ($i['fin']->getTimestamp() - $i['inicio']->getTimestamp()) / 60;

                return [
                    'inicio' => $i['inicio'],
                    'fin' => $i['fin'],
                    'en_curso' => $i['en_curso'],
                    'minutos' => round($min, 1),
                    'litros' => $caudal !== null ? round($min * $caudal) : null,
                ];
            })
            ->reverse()
            ->values()
            ->all();

        return [
            'n_dias' => $nDias,
            'total' => $this->resumenPeriodo($intervalos, $primerDia, $fin),
            'dias' => $dias,
            'detalle' => $detalle,
        ];
    }

    /**
     * Últimos 14 días, 8 semanas (lunes a domingo) y 6 meses, más el período actual.
     * Todo se calcula con una sola consulta de ciclos.
     */
    public function series(): array
    {
        $ahora = now();

        $primerDia = $ahora->copy()->startOfDay()->subDays(13);
        $primeraSemana = $ahora->copy()->startOfWeek()->subWeeks(7);
        $primerMes = $ahora->copy()->startOfMonth()->subMonthsNoOverflow(5);

        $intervalos = $this->intervalos(min($primerDia, $primeraSemana, $primerMes), $ahora);

        $dias = $semanas = $meses = [];

        for ($k = 0; $k < 14; $k++) {
            $ini = $ahora->copy()->startOfDay()->subDays($k);
            $dias[] = ['etiqueta' => ucfirst($ini->translatedFormat('D d/m'))]
                + $this->resumenPeriodo($intervalos, $ini, $ini->copy()->addDay());
        }

        for ($k = 0; $k < 8; $k++) {
            $ini = $ahora->copy()->startOfWeek()->subWeeks($k);
            $semanas[] = ['etiqueta' => $ini->format('d/m').' – '.$ini->copy()->addDays(6)->format('d/m')]
                + $this->resumenPeriodo($intervalos, $ini, $ini->copy()->addWeek());
        }

        for ($k = 0; $k < 6; $k++) {
            $ini = $ahora->copy()->startOfMonth()->subMonthsNoOverflow($k);
            $meses[] = ['etiqueta' => ucfirst($ini->translatedFormat('F Y'))]
                + $this->resumenPeriodo($intervalos, $ini, $ini->copy()->addMonth());
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