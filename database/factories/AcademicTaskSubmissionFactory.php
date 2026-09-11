<?php

namespace Database\Factories;

use App\Models\AcademicTask;
use App\Models\AcademicTaskSubmission;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcademicTaskSubmission> */
class AcademicTaskSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_task_id' => AcademicTask::factory(),
            'enrollment_id' => Enrollment::factory(),
            'disk' => 'local',
            'path' => 'academic-submissions/example.txt',
            'original_name' => 'entrega.txt',
            'mime_type' => 'text/plain',
            'size' => 12,
            'submitted_at' => now(),
            'feedback' => null,
        ];
    }
}
