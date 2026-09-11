<?php

namespace App\Models;

use Database\Factories\PeriodFinalGradeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $academic_period_id
 * @property int $subject_id
 * @property float $final_score
 * @property int $activities_count
 * @property array<int, array<string, mixed>> $activity_breakdown
 * @property Carbon $closed_at
 * @property-read AcademicPeriod $academicPeriod
 * @property-read Subject $subject
 */
#[Fillable(['enrollment_id', 'academic_period_id', 'subject_id', 'final_score', 'activities_count', 'activity_breakdown', 'closed_at'])]
class PeriodFinalGrade extends Model
{
    /** @use HasFactory<PeriodFinalGradeFactory> */
    use HasFactory;

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
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

    protected function casts(): array
    {
        return ['final_score' => 'float', 'activities_count' => 'integer', 'activity_breakdown' => 'array', 'closed_at' => 'datetime'];
    }
}
