<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\ScheduleEntry;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleEntry>
 */
class ScheduleEntryFactory extends Factory
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
            'teacher_id' => User::factory()->withRole(UserRole::Teacher),
            'day_of_week' => fake()->numberBetween(1, 5),
            'starts_at' => '07:00',
            'ends_at' => '08:00',
            'room' => fake()->optional()->bothify('Aula ##'),
        ];
    }
}
