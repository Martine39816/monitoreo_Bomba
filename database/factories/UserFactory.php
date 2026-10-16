<?php

namespace Database\Factories;

use App\Models\CentroSalud;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('##########'),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'rol' => User::ROL_TECNICO,
            'estado' => true,
            'centros_salud_id' => CentroSalud::factory(),
        ];
    }

    /**
     * Indica que el usuario es administrador.
     */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => User::ROL_ADMINISTRADOR,
        ]);
    }
}
