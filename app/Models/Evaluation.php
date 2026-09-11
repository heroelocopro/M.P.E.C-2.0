<?php

namespace App\Models;

use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['teacher_assignment_id', 'title', 'description', 'questions', 'due_at'])]
class Evaluation extends Model
{
    /** @return BelongsTo<AcademicTask, $this> */
    public function academicTask(): BelongsTo
    {
        return $this->belongsTo(AcademicTask::class);
    }

    /** @use HasFactory<EvaluationFactory> */
    use HasFactory;

    protected $hidden = ['questions'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['questions' => 'array', 'due_at' => 'datetime'];
    }

    /** @return BelongsTo<TeacherAssignment, $this> */
    public function teacherAssignment(): BelongsTo
    {
        return $this->belongsTo(TeacherAssignment::class);
    }

    /** @return HasMany<EvaluationAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(EvaluationAttempt::class);
    }
}
