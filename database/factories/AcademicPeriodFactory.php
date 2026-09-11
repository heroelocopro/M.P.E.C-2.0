<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicPeriod>
 */
class AcademicPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2000, 2100);

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'Periodo '.fake()->numberBetween(1, 4),
            'number' => fake()->numberBetween(1, 4),
            'starts_at' => "{$year}-01-20",
            'ends_at' => "{$year}-03-30",
            'is_closed' => false,
        ];
    }
}
