<?php

namespace App\Models;

use Database\Factories\AcademicTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property Carbon $due_at
 * @property Carbon|null $published_at
 * @property int $grades_count
 * @property string|float|null $grades_avg_score
 * @property-read Subject $subject
 * @property-read User $assignedBy
 */
#[Fillable(['classroom_id', 'academic_period_id', 'subject_id', 'assigned_by_id', 'title', 'description', 'type', 'due_at', 'published_at'])]
class AcademicTask extends Model
{
    /** @use HasFactory<AcademicTaskFactory> */
    use HasFactory;

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return BelongsTo<AcademicPeriod, $this> */
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    /** @return HasMany<AcademicTaskAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(AcademicTaskAttachment::class);
    }

    /** @return HasMany<AcademicTaskSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(AcademicTaskSubmission::class);
    }

    /** @return HasMany<TaskGrade, $this> */
    public function grades(): HasMany
    {
        return $this->hasMany(TaskGrade::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'published_at' => 'datetime'];
    }
}
