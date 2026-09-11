<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Evaluation;
use App\Models\EvaluationAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationAttempt>
 */
class EvaluationAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'user_id' => User::factory()->withRole(UserRole::Student),
            'answers' => [1],
            'correct_count' => 1,
            'score' => 100,
            'graded_at' => now(),
        ];
    }
}
