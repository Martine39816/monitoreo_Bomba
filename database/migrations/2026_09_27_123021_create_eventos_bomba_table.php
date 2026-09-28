<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_bomba', function (Blueprint $table) {
            $table->id();
            $table->boolean('encendido'); // true = se encendio, false = se apago
            $table->dateTime('fecha_hora');
            $table->string('origen', 20)->default('manual'); // manual | automatico | api
            $table->foreignId('bombas_id')
                ->constrained('bombas')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();

            $table->index(['bombas_id', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_bomba');
    }
};