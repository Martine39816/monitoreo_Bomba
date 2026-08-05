<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->date('fecha');
            $table->text('descripcion')->nullable();
            $table->text('observaciones')->nullable();
            $table->date('proximo_mantenimiento')->nullable();
            $table->foreignId('bombas_id')
                ->constrained('bombas')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('usuarios_id')->nullable()
                ->constrained('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('alertas_id')->nullable()
                ->constrained('alertas')
                ->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE mantenimientos ADD CONSTRAINT chk_mantenimientos_tipo CHECK (tipo IN ('preventivo','correctivo','predictivo','emergencia'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
