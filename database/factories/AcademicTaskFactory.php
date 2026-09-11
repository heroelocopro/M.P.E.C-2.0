<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\AcademicTask;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicTask>
 */
class AcademicTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'assigned_by_id' => User::factory()->withRole(UserRole::Teacher),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => 'tarea',
            'due_at' => now()->addWeek(),
            'published_at' => now(),
        ];
    }
}
