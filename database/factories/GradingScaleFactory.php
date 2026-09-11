<?php

namespace Database\Factories;

use App\Models\GradingScale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradingScale>
 */
class GradingScaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'minimum' => 0,
            'maximum' => 5,
            'passing' => 3,
        ];
    }
}
