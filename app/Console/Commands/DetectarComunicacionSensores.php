<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\Sensor;
use Illuminate\Console\Command;

class DetectarComunicacionSensores extends Command
{
    protected $signature = 'sensores:detectar-comunicacion';
    protected $description = 'Genera alertas cuando un sensor deja de enviar lecturas.';

    public function handle(): int
    {
        $limiteMinutos = max(1, (int) config('monitoreo.sensor_sin_comunicacion_minutos', 5));
        $limite = now()->subMinutes($limiteMinutos);
        $sensores = Sensor::with('bomba')->where('estado', 'activo')->get();

        foreach ($sensores as $sensor) {
            if (! $sensor->bomba || $sensor->bomba->estado !== 'activo') continue;

            $ultima = $sensor->lecturas()->latest('fecha_hora')->first();
            $sinSenal = ! $ultima || $ultima->fecha_hora->lt($limite);
            if (! $sinSenal) continue;

            $pendiente = Alerta::where('sensores_id', $sensor->id)
                ->where('tipo', 'sensor_desconectado')->where('atendida', false)->exists();
            if ($pendiente) continue;

            Alerta::create([
                'tipo' => 'sensor_desconectado',
                'descripcion' => 'El sensor '.$sensor->nombre.' no envía lecturas desde hace '.
                    ($ultima ? $ultima->fecha_hora->diffForHumans() : 'que fue activado').
                    '. Revisar alimentación, cableado, ESP32 y conexión de red.',
                'valor_detectado' => $ultima ? now()->diffInMinutes($ultima->fecha_hora) : null,
                'valor_permitido' => $limiteMinutos,
                'fecha_hora' => now(),
                'atendida' => false,
                'sensores_id' => $sensor->id,
                'bombas_id' => $sensor->bombas_id,
            ]);
            $this->warn('Sin comunicación: '.$sensor->codigo);
        }

        $this->info('Revisión de comunicación terminada.');
        return self::SUCCESS;
    }
}
