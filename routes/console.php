<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Revisa cada minuto el comportamiento de las bombas (tiempo encendida,
// ciclos frecuentes, riesgo de rebalse) y genera alertas automaticas.
Schedule::command('bombas:analizar-comportamiento')->everyMinute();