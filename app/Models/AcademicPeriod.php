<?php

namespace App\Models;

use Database\Factories\AcademicPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $academic_year_id
 * @property int $id
 * @property string $name
 * @property int $number
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property bool $is_closed
 * @property-read AcademicYear $academicYear
 */
#[Fillable(['academic_year_id', 'name', 'number', 'starts_at', 'ends_at', 'is_closed'])]
class AcademicPeriod extends Model
{
    /** @use HasFactory<AcademicPeriodFactory> */
    use HasFactory;

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return HasMany<AcademicTask, $this> */
    public function academicTasks(): HasMany
    {
        return $this->hasMany(AcademicTask::class);
    }

    /** @return HasMany<PeriodFinalGrade, $this> */
    public function finalGrades(): HasMany
    {
        return $this->hasMany(PeriodFinalGrade::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['number' => 'integer', 'starts_at' => 'date', 'ends_at' => 'date', 'is_closed' => 'boolean'];
    }
}
