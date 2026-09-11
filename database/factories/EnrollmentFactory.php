<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_number' => fake()->unique()->numerify('MAT-########'),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'grade_id' => Grade::factory(),
            'enrolled_at' => now()->toDateString(),
            'status' => EnrollmentStatus::Active,
            'observations' => null,
            'completed_at' => null,
        ];
    }
}
