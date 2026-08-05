<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos_iot', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('modelo', 80)->nullable();
            $table->string('tipo_dispositivo', 30)->nullable();
            $table->integer('puerto')->nullable();
            $table->string('direccion_ip', 50)->nullable();
            $table->string('mac_address', 50)->nullable();
            $table->string('firmware', 50)->nullable();
            $table->string('estado', 20)->default('activo');
            // API key propia del dispositivo (autenticacion IoT independiente de usuarios)
            $table->string('api_key', 64)->unique()->nullable();
            $table->foreignId('centros_salud_id')
                ->constrained('centros_salud')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE dispositivos_iot ADD CONSTRAINT chk_dispositivos_estado CHECK (estado IN ('activo','inactivo','mantenimiento','fuera_servicio','desconectado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos_iot');
    }
};
