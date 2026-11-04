
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bombas', function (Blueprint $table) {
            $table->boolean('bloqueo_remoto')
                ->default(false)
                ->after('encendido');
        });
    }

    public function down(): void
    {
        Schema::table('bombas', function (Blueprint $table) {
            $table->dropColumn('bloqueo_remoto');
        });
    }
};
