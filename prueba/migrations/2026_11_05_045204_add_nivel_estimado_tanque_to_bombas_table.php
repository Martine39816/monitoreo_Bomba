<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bombas', function (Blueprint $table) {
            $table->decimal('nivel_tanque_estimado_pct', 5, 2)
                ->nullable();

            $table->timestamp('nivel_tanque_ultimo_lleno_en')
                ->nullable();

            $table->timestamp('nivel_tanque_ultimo_inicio_en')
                ->nullable();

            $table->decimal('nivel_tanque_consumo_lpm', 10, 4)
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bombas', function (Blueprint $table) {
            $table->dropColumn([
                'nivel_tanque_estimado_pct',
                'nivel_tanque_ultimo_lleno_en',
                'nivel_tanque_ultimo_inicio_en',
                'nivel_tanque_consumo_lpm',
            ]);
        });
    }
};