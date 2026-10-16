<?php

namespace Database\Factories;

use App\Models\CentroSalud;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CentroSalud>
 */
class CentroSaludFactory extends Factory
{
    protected $model = CentroSalud::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->bothify('CS-####')),
            'nombre' => 'Centro de Salud '.fake()->city(),
            'direccion' => fake()->address(),
            'telefono' => fake()->numerify('##########'),
        ];
    }
}
