<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 30);
            $table->string('descripcion', 255)->nullable();
            $table->decimal('valor_detectado', 12, 3)->nullable();
            $table->decimal('valor_permitido', 12, 3)->nullable();
            $table->dateTime('fecha_hora');
            $table->boolean('atendida')->default(false);
            $table->dateTime('fecha_atencion')->nullable();
            $table->foreignId('sensores_id')->nullable()
                ->constrained('sensores')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('usuario_atencion_id')->nullable()
                ->constrained('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('bombas_id')
                ->constrained('bombas')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE alertas ADD CONSTRAINT chk_alertas_tipo CHECK (tipo IN ('nivel_bajo','nivel_alto','bomba_apagada','falla_electrica','sobrecorriente','temperatura_alta','vibracion_alta','sensor_desconectado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
