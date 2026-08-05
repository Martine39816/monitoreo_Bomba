<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 120);
            $table->string('tipo', 20);
            $table->string('marca', 80)->nullable();
            $table->string('modelo', 80)->nullable();
            $table->string('unidad_medida', 30)->nullable();
            $table->decimal('valor_minimo', 10, 2)->nullable();
            $table->decimal('valor_maximo', 10, 2)->nullable();
            $table->decimal('precision_sensor', 6, 2)->nullable();
            $table->string('estado', 20)->default('activo');
            $table->date('fecha_instalacion')->nullable();
            $table->string('ubicacion', 100)->nullable();
            $table->foreignId('dispositivos_iot_id')
                ->constrained('dispositivos_iot')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('bombas_id')
                ->constrained('bombas')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE sensores ADD CONSTRAINT chk_sensores_tipo CHECK (tipo IN ('temperatura','vibracion','corriente','presion','nivel'))");
        DB::statement("ALTER TABLE sensores ADD CONSTRAINT chk_sensores_estado CHECK (estado IN ('activo','inactivo','mantenimiento','fuera_servicio','desconectado'))");
        DB::statement("ALTER TABLE sensores ADD CONSTRAINT chk_sensor_rango CHECK (valor_minimo IS NULL OR valor_maximo IS NULL OR valor_minimo < valor_maximo)");
    }

    public function down(): void
    {
        Schema::dropIfExists('sensores');
    }
};
