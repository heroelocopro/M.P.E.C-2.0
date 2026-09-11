<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2000, 2100);

        return [
            'name' => (string) $year,
            'starts_at' => "{$year}-01-20",
            'ends_at' => "{$year}-11-30",
            'is_active' => false,
        ];
    }
}
