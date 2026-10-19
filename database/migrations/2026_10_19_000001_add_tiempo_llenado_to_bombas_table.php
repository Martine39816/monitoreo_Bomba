<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega UNA columna a `bombas` (no crea tablas): los minutos que tarda la
 * bomba en subir el tanque desde el nivel de encendido (30 %) al de apagado
 * (90 %). Se usa para estimar el caudal y los litros de cada ciclo.
 * Es opcional: si queda vacío, el sistema lo estima con los ciclos reales.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bombas', 'tiempo_llenado_min')) {
            Schema::table('bombas', function (Blueprint $table) {
                $table->decimal('tiempo_llenado_min', 6, 1)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bombas', 'tiempo_llenado_min')) {
            Schema::table('bombas', function (Blueprint $table) {
                $table->dropColumn('tiempo_llenado_min');
            });
        }
    }
};