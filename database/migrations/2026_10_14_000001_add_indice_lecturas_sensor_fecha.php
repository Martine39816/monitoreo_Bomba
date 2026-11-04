<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice compuesto para las consultas más frecuentes sobre `lecturas`:
 * "últimas lecturas de un sensor" (ultimaLectura, ficha de bomba, gráficos)
 * y la depuración por fecha. No agrega tablas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('lecturas', ['sensores_id', 'fecha_hora'])) {
            Schema::table('lecturas', function (Blueprint $table) {
                $table->index(['sensores_id', 'fecha_hora'], 'lecturas_sensor_fecha_idx');
            });
        }

        if (! Schema::hasIndex('lecturas', ['fecha_hora'])) {
            Schema::table('lecturas', function (Blueprint $table) {
                $table->index('fecha_hora', 'lecturas_fecha_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('lecturas', function (Blueprint $table) {
            if (Schema::hasIndex('lecturas', 'lecturas_sensor_fecha_idx')) {
                $table->dropIndex('lecturas_sensor_fecha_idx');
            }
            if (Schema::hasIndex('lecturas', 'lecturas_fecha_idx')) {
                $table->dropIndex('lecturas_fecha_idx');
            }
        });
    }
};