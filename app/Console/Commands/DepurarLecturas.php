<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Controla el crecimiento de la tabla `lecturas`.
 *
 * Uso:
 *   php artisan lecturas:depurar --dias=90
 *       Borra todas las lecturas con más de 90 días.
 *
 *   php artisan lecturas:depurar --dias=30 --conservar-horaria
 *       En lugar de borrar, deja 1 lectura por sensor y por hora
 *       en lo anterior a 30 días (conserva la tendencia histórica).
 *
 * Las alertas guardan su propio valor_detectado, así que no dependen
 * de las lecturas que se depuran.
 */
class DepurarLecturas extends Command
{
    protected $signature = 'lecturas:depurar
                            {--dias=90 : Se depura lo anterior a esta cantidad de días}
                            {--conservar-horaria : Deja 1 lectura por sensor y por hora en vez de borrar todo}';

    protected $description = 'Elimina o adelgaza las lecturas antiguas para que la tabla no crezca sin control';

    public function handle(): int
    {
        $dias = max(1, (int) $this->option('dias'));
        $corte = now()->subDays($dias)->startOfDay();

        $total = $this->option('conservar-horaria')
            ? $this->adelgazar($corte)
            : $this->borrar($corte);

        $this->info("Lecturas eliminadas: {$total} (anteriores al {$corte->format('d/m/Y')}).");

        return self::SUCCESS;
    }

    /** Borra en bloques de 5000 para no bloquear la tabla. */
    private function borrar(Carbon $corte): int
    {
        $total = 0;

        do {
            $n = DB::table('lecturas')
                ->where('fecha_hora', '<', $corte)
                ->limit(5000)
                ->delete();

            $total += $n;
        } while ($n > 0);

        return $total;
    }

    /** Deja la primera lectura de cada hora, por sensor, día por día. */
    private function adelgazar(Carbon $corte): int
    {
        $total = 0;

        $dias = DB::table('lecturas')
            ->where('fecha_hora', '<', $corte)
            ->selectRaw('DATE(fecha_hora) as dia')
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('dia');

        foreach ($dias as $dia) {
            $desde = $dia.' 00:00:00';
            $hasta = Carbon::parse($dia)->addDay()->format('Y-m-d H:i:s');

            $total += DB::delete(
                'DELETE l FROM lecturas AS l
                 JOIN (
                     SELECT id FROM (
                         SELECT id,
                                ROW_NUMBER() OVER (
                                    PARTITION BY sensores_id, HOUR(fecha_hora)
                                    ORDER BY fecha_hora
                                ) AS rn
                         FROM lecturas
                         WHERE fecha_hora >= ? AND fecha_hora < ?
                     ) AS t
                     WHERE t.rn > 1
                 ) AS x ON x.id = l.id',
                [$desde, $hasta]
            );
        }

        return $total;
    }
}