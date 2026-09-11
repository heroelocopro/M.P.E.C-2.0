<?php

namespace App\Models;

use Database\Factories\EvaluationAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['evaluation_id', 'user_id', 'answers', 'correct_count', 'score', 'manual_scores', 'feedback', 'graded_at'])]
class EvaluationAttempt extends Model
{
    /** @use HasFactory<EvaluationAttemptFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['answers' => 'array', 'score' => 'float', 'manual_scores' => 'array', 'graded_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
