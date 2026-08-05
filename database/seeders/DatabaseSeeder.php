<?php

namespace Database\Seeders;

use App\Models\CentroSalud;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea el primer centro de salud y el primer Administrador,
     * necesarios porque ya no existe auto-registro publico.
     *
     * IMPORTANTE: cambia la contrasena inmediatamente despues del primer login.
     */
    public function run(): void
    {
        $centro = CentroSalud::firstOrCreate(
            ['codigo' => 'CS-001'],
            [
                'nombre' => 'Centro de Salud Principal',
                'direccion' => 'Por definir',
                'telefono' => null,
            ]
        );

        User::firstOrCreate(
            ['correo' => 'admin@monitoreobomba.local'],
            [
                'nombre' => 'Administrador',
                'apellido' => 'Sistema',
                'password' => 'CambiarInmediatamente123!',
                'rol' => User::ROL_ADMINISTRADOR,
                'estado' => true,
                'centros_salud_id' => $centro->id,
            ]
        );
    }
}
