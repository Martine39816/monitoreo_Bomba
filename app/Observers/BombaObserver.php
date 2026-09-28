<?php

namespace App\Observers;

use App\Models\Bomba;
use App\Models\EventoBomba;

class BombaObserver
{
    /**
     * Cada vez que el estado real de funcionamiento (medido por corriente)
     * cambia, se guarda un registro en eventos_bomba.
     */
    public function updated(Bomba $bomba): void
    {
        if (! $bomba->wasChanged('funcionando')) {
            return;
        }

        EventoBomba::create([
            'bombas_id' => $bomba->id,
            'encendido' => $bomba->funcionando,
            'fecha_hora' => now(),
            'origen' => 'sensor_corriente',
        ]);
    }
}