<?php

namespace Database\Factories;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->numerify('Grado ##'),
            'code' => fake()->unique()->bothify('GR-###-??'),
            'sort_order' => fake()->numberBetween(1, 20),
            'is_final' => false,
            'is_active' => true,
        ];
    }
}
