<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Recorre las páginas principales autenticado como administrador
     * para verificar que ninguna devuelve error 500.
     */
    public function test_paginas_principales_renderizan(): void
    {
        $admin = User::factory()->administrador()->create();

        $rutas = [
            'dashboard',
            'bombas.index',
            'sensores.index',
            'dispositivos.index',
            'mantenimientos.index',
            'alertas.index',
            'lecturas.index',
            'lecturas.create',
            'centros.index',
            'users.index',
        ];

        foreach ($rutas as $ruta) {
            $response = $this->actingAs($admin)->get(route($ruta));
            $response->assertSuccessful();
        }
    }

    /**
     * Recorre las páginas principales como técnico (rol no administrador).
     */
    public function test_paginas_principales_como_tecnico(): void
    {
        $tecnico = User::factory()->create();

        $rutas = [
            'dashboard',
            'bombas.index',
            'sensores.index',
            'alertas.index',
            'lecturas.index',
        ];

        foreach ($rutas as $ruta) {
            $response = $this->actingAs($tecnico)->get(route($ruta));
            $response->assertSuccessful();
        }
    }
}
