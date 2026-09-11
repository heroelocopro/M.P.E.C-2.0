<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\TeacherAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_assignment_id' => TeacherAssignment::factory(),
            'title' => fake()->sentence(),
            'questions' => [['type' => 'choice', 'prompt' => '¿Cuánto es 2 + 2?', 'options' => ['3', '4', '5', '6'], 'correct' => 1]],
            'due_at' => now()->addWeek(),
        ];
    }
}
