<?php

namespace Database\Seeders;

use App\Models\ForumTopic;
use Illuminate\Database\Seeder;

class ForumReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topic = ForumTopic::query()->where('title', 'Preguntas del curso')->first();
        if ($topic) {
            $topic->replies()->firstOrCreate([
                'user_id' => $topic->user_id,
                'body' => 'Recuerda explicar qué parte del ejercicio necesitas revisar.',
            ]);
        }
    }
}
