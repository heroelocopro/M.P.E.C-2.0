<?php

namespace Database\Seeders;

use App\Models\Evaluation;
use App\Models\TeacherAssignment;
use Illuminate\Database\Seeder;

class EvaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assignment = TeacherAssignment::query()->first();
        if ($assignment) {
            Evaluation::query()->firstOrCreate(
                ['teacher_assignment_id' => $assignment->id, 'title' => 'Evaluación de práctica'],
                ['due_at' => now()->addWeek(), 'questions' => [
                    ['type' => 'choice', 'prompt' => '¿Cuánto es 2 + 2?', 'options' => ['3', '4', '5', '6'], 'correct' => 1],
                    ['type' => 'open', 'prompt' => 'Explica cómo encontraste la respuesta.'],
                ]],
            );
        }
    }
}
