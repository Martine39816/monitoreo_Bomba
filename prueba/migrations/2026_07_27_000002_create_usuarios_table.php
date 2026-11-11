<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Tabla de autenticacion real del sistema (reemplaza a 'users').
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('correo', 120)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('rol', 20)->default('operador');
            $table->boolean('estado')->default(true);
            $table->dateTime('ultimo_acceso')->nullable();
            $table->foreignId('centros_salud_id')
                ->constrained('centros_salud')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT chk_usuarios_rol CHECK (rol IN ('administrador','tecnico','operador'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
