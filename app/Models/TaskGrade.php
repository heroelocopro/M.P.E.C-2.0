<?php

namespace App\Models;

use Database\Factories\TaskGradeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['academic_task_id', 'enrollment_id', 'graded_by_id', 'score', 'observations'])]
class TaskGrade extends Model
{
    /** @use HasFactory<TaskGradeFactory> */
    use HasFactory;

    /** @return BelongsTo<AcademicTask, $this> */
    public function academicTask(): BelongsTo
    {
        return $this->belongsTo(AcademicTask::class);
    }

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }
}
