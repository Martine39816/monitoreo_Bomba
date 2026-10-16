<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturas', function (Blueprint $table) {
            $table->index('sensores_id', 'lecturas_sensores_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('lecturas', function (Blueprint $table) {
            $table->dropIndex('lecturas_sensores_id_index');
        });
    }
};
