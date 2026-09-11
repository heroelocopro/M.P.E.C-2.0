<?php

namespace Database\Seeders;

use App\Models\ForumTopic;
use App\Models\TeacherAssignment;
use Illuminate\Database\Seeder;

class ForumTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assignment = TeacherAssignment::query()->first();
        if ($assignment) {
            ForumTopic::query()->firstOrCreate(
                ['classroom_id' => $assignment->classroom_id, 'title' => 'Preguntas del curso'],
                ['user_id' => $assignment->teacher_id, 'body' => 'Comparte aquí tus preguntas sobre las actividades del curso.'],
            );
        }
    }
}
