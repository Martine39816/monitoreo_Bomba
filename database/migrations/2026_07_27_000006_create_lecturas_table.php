<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->decimal('valor_medido', 12, 3);
            $table->timestamp('fecha_hora')->useCurrent();
            $table->foreignId('sensores_id')
                ->constrained('sensores')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE lecturas ADD CONSTRAINT chk_lectura_valor CHECK (valor_medido >= 0)");
        DB::statement("CREATE INDEX idx_lecturas_fecha_hora ON lecturas (fecha_hora)");
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};
