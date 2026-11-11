<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bombas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('serie', 80)->unique();
            $table->decimal('potencia_hp', 5, 2)->nullable();
            $table->decimal('voltaje_nominal', 6, 2)->nullable();
            $table->decimal('corriente_nominal', 6, 2)->nullable();
            $table->decimal('caudal_min', 10, 2)->nullable();
            $table->decimal('caudal_max', 10, 2)->nullable();
            $table->decimal('altura_maxima', 10, 2)->nullable();
            $table->decimal('temperatura_maxima', 5, 2)->nullable();
            $table->decimal('presion_maxima', 6, 2)->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->boolean('modo_operacion')->default(false); // 0 manual, 1 automatico
            $table->boolean('encendido')->default(false);
            $table->string('estado', 20)->default('activo');
            // Datos del tanque que alimenta (fusion de la antigua tabla tanques)
            $table->string('tanque_codigo', 30)->nullable();
            $table->decimal('tanque_capacidad_litros', 10, 2)->nullable();
            $table->decimal('tanque_altura_metros', 6, 2)->nullable();
            $table->decimal('tanque_diametro_metros', 6, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('centros_salud_id')
                ->constrained('centros_salud')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE bombas ADD CONSTRAINT chk_bombas_estado CHECK (estado IN ('activo','inactivo','mantenimiento','fuera_servicio','desconectado'))");
        DB::statement("ALTER TABLE bombas ADD CONSTRAINT chk_bomba_potencia CHECK (potencia_hp IS NULL OR potencia_hp > 0)");
        DB::statement("ALTER TABLE bombas ADD CONSTRAINT chk_bomba_voltaje CHECK (voltaje_nominal IS NULL OR voltaje_nominal > 0)");
        DB::statement("ALTER TABLE bombas ADD CONSTRAINT chk_bomba_corriente CHECK (corriente_nominal IS NULL OR corriente_nominal >= 0)");
        DB::statement("ALTER TABLE bombas ADD CONSTRAINT chk_tanque_capacidad CHECK (tanque_capacidad_litros IS NULL OR tanque_capacidad_litros > 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('bombas');
    }
};
