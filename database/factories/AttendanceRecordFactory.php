<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'recorded_by_id' => User::factory()->withRole(UserRole::Teacher),
            'date' => now()->toDateString(),
            'status' => 'presente',
            'notes' => null,
        ];
    }
}
