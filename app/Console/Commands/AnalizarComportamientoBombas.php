<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\Bomba;
use Illuminate\Console\Command;

class AnalizarComportamientoBombas extends Command
{
    protected $signature = 'bombas:analizar-comportamiento';

    protected $description = 'Revisa tiempo de funcionamiento, ciclos y riesgo de rebalse de cada bomba, y genera alertas automaticas.';

    public function handle(): int
    {
        $tiempoAdvertencia = config('monitoreo.tiempo_advertencia_minutos');
        $rebalseMinutos = config('monitoreo.rebalse_tiempo_minutos');
        $ciclosMax = config('monitoreo.ciclos_max');
        $ciclosPeriodo = config('monitoreo.ciclos_periodo_minutos');

        $bombas = Bomba::with('sensores')->where('estado', 'activo')->get();

        foreach ($bombas as $bomba) {
            $this->revisarTiempoYRebalse($bomba, $tiempoAdvertencia, $rebalseMinutos);
            $this->revisarCiclos($bomba, $ciclosMax, $ciclosPeriodo);
        }

        $this->info('Analisis de comportamiento completado para '.$bombas->count().' bomba(s).');

        return self::SUCCESS;
    }

    /**
     * Si la bomba lleva encendida mas del umbral, genera 'tiempo_excedido'.
     * Si ademas el nivel del tanque ya esta al maximo, la alerta pasa a ser
     * 'posible_rebalse' (mas critica), con un umbral de tiempo mas sensible.
     */
    private function revisarTiempoYRebalse(Bomba $bomba, int $tiempoAdvertencia, int $rebalseMinutos): void
    {
        $minutosEncendida = $bomba->minutosEncendidaContinuo();

        if ($minutosEncendida === null) {
            return; // esta apagada, o no hay historial confiable todavia
        }

        $sensorNivel = $bomba->sensores->firstWhere('tipo', 'nivel');
        $nivelEnMaximo = false;

        if ($sensorNivel && $sensorNivel->valor_maximo !== null) {
            $ultimaLectura = $sensorNivel->ultimaLectura;
            $nivelEnMaximo = $ultimaLectura && (float) $ultimaLectura->valor_medido >= (float) $sensorNivel->valor_maximo;
        }

        if ($nivelEnMaximo && $minutosEncendida >= $rebalseMinutos) {
            $this->generarAlertaSiNoExiste($bomba, 'posible_rebalse', sprintf(
                'La bomba lleva %d minutos encendida y el nivel del tanque ya alcanzo su maximo. '.
                'Posible falla del flotador o sistema de corte: riesgo de rebalse.',
                $minutosEncendida
            ), $minutosEncendida, $rebalseMinutos);

            return; // no generamos ademas la alerta generica de tiempo excedido
        }

        if ($minutosEncendida >= $tiempoAdvertencia) {
            $this->generarAlertaSiNoExiste($bomba, 'tiempo_excedido', sprintf(
                'La bomba lleva %d minutos encendida de forma continua, superando el tiempo normal de funcionamiento (%d min).',
                $minutosEncendida, $tiempoAdvertencia
            ), $minutosEncendida, $tiempoAdvertencia);
        }
    }

    /**
     * Detecta encendido/apagado repetido en poco tiempo (posible falla
     * electrica, sensor de nivel oscilando, o mala configuracion automatica).
     */
    private function revisarCiclos(Bomba $bomba, int $ciclosMax, int $ciclosPeriodo): void
    {
        $ciclos = $bomba->ciclosEncendidoEn($ciclosPeriodo);

        if ($ciclos >= $ciclosMax) {
            $this->generarAlertaSiNoExiste($bomba, 'ciclos_frecuentes', sprintf(
                'La bomba se encendio %d veces en los ultimos %d minutos. Esto puede indicar una falla electrica o un sensor inestable.',
                $ciclos, $ciclosPeriodo
            ), $ciclos, $ciclosMax);
        }
    }

    /**
     * Evita duplicar alertas: si ya existe una pendiente del mismo tipo para
     * esta bomba, no crea otra.
     */
    private function generarAlertaSiNoExiste(Bomba $bomba, string $tipo, string $descripcion, float $valorDetectado, float $valorPermitido): void
    {
        $yaExistePendiente = Alerta::where('bombas_id', $bomba->id)
            ->where('tipo', $tipo)
            ->where('atendida', false)
            ->exists();

        if ($yaExistePendiente) {
            return;
        }

        Alerta::create([
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'valor_detectado' => $valorDetectado,
            'valor_permitido' => $valorPermitido,
            'fecha_hora' => now(),
            'atendida' => false,
            'bombas_id' => $bomba->id,
        ]);

        $this->warn("[{$bomba->nombre}] Alerta generada: {$tipo}");
    }
}