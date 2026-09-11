<?php

namespace Database\Seeders;

use App\Models\Evaluation;
use Illuminate\Database\Seeder;

class EvaluationAttemptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $evaluation = Evaluation::query()->where('title', 'Evaluación de práctica')->first();
        $enrollment = $evaluation?->teacherAssignment->classroom->enrollments()
            ->where('status', 'activa')->whereHas('student.user')->with('student')->first();
        if ($evaluation && $enrollment) {
            $evaluation->attempts()->firstOrCreate(
                ['user_id' => $enrollment->student->user_id],
                ['answers' => [1, 'Sumé dos unidades a dos.'], 'correct_count' => 1, 'score' => 50],
            );
        }
    }
}
