<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT chk_usuarios_rol CHECK (rol IN ('administrador','tecnico','director'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_rol');
        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT chk_usuarios_rol CHECK (rol IN ('administrador','tecnico','operador'))");
    }
};