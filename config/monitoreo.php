<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Umbrales de comportamiento de bombas
    |--------------------------------------------------------------------------
    */

    // Minutos funcionando sin parar antes de alertar 'tiempo_excedido'.
    'tiempo_advertencia_minutos' => (int) env('BOMBA_TIEMPO_ADVERTENCIA_MIN', 15),

    // Minutos funcionando con el nivel al maximo antes de alertar 'posible_rebalse'.
    'rebalse_tiempo_minutos' => (int) env('BOMBA_REBALSE_TIEMPO_MIN', 5),

    // Arranques dentro de la ventana de tiempo que se consideran 'ciclos_frecuentes'.
    'ciclos_max' => (int) env('BOMBA_CICLOS_MAX', 4),
    'ciclos_periodo_minutos' => (int) env('BOMBA_CICLOS_PERIODO_MIN', 10),

    // Deteccion de funcionamiento por corriente (en amperios).
    // La bomba se considera FUNCIONANDO cuando la corriente sube de UMBRAL_ON,
    // y DETENIDA cuando baja de UMBRAL_OFF. Usar dos valores distintos evita
    // que el estado "parpadee" si la corriente oscila cerca del limite.
    'corriente_umbral_encendido' => (float) env('BOMBA_CORRIENTE_UMBRAL_ON', 0.5),
    'corriente_umbral_apagado' => (float) env('BOMBA_CORRIENTE_UMBRAL_OFF', 0.3),

];