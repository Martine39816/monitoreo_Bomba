<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centros_salud', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 45)->unique();
            $table->string('nombre', 45);
            $table->string('direccion', 255);
            $table->string('telefono', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centros_salud');
    }
};
