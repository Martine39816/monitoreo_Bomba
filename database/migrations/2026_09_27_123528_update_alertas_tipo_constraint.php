<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE alertas DROP CHECK chk_alertas_tipo');

        DB::statement("ALTER TABLE alertas ADD CONSTRAINT chk_alertas_tipo CHECK (tipo IN (
            'nivel_bajo','nivel_alto','bomba_apagada','falla_electrica',
            'sobrecorriente','temperatura_alta','vibracion_alta','sensor_desconectado',
            'tiempo_excedido','ciclos_frecuentes','posible_rebalse'
        ))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE alertas DROP CHECK chk_alertas_tipo');

        DB::statement("ALTER TABLE alertas ADD CONSTRAINT chk_alertas_tipo CHECK (tipo IN (
            'nivel_bajo','nivel_alto','bomba_apagada','falla_electrica',
            'sobrecorriente','temperatura_alta','vibracion_alta','sensor_desconectado'
        ))");
    }
};