<?php

namespace Database\Factories;

use App\Models\AcademicTask;
use App\Models\Enrollment;
use App\Models\TaskGrade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskGrade>
 */
class TaskGradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_task_id' => AcademicTask::factory(),
            'enrollment_id' => Enrollment::factory(),
            'graded_by_id' => User::factory(),
            'score' => fake()->randomFloat(2, 0, 5),
            'observations' => fake()->optional()->sentence(),
        ];
    }
}
