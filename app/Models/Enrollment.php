<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $enrollment_number
 * @property Carbon $enrolled_at
 * @property EnrollmentStatus $status
 * @property Carbon|null $completed_at
 * @property int $academic_year_id
 * @property int|null $classroom_id
 * @property string|null $observations
 * @property-read Student $student
 * @property-read AcademicYear $academicYear
 * @property-read Grade $grade
 * @property-read Classroom|null $classroom
 * @property-read Collection<int, TaskGrade> $taskGrades
 * @property-read Collection<int, PeriodFinalGrade> $periodFinalGrades
 */
#[Fillable(['enrollment_number', 'student_id', 'academic_year_id', 'grade_id', 'classroom_id', 'enrolled_at', 'status', 'observations', 'completed_at'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<Grade, $this> */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** @return HasMany<TaskGrade, $this> */
    public function taskGrades(): HasMany
    {
        return $this->hasMany(TaskGrade::class);
    }

    /** @return HasMany<PeriodFinalGrade, $this> */
    public function periodFinalGrades(): HasMany
    {
        return $this->hasMany(PeriodFinalGrade::class);
    }

    /** @return HasOne<AcademicYearResult, $this> */
    public function academicYearResult(): HasOne
    {
        return $this->hasOne(AcademicYearResult::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['enrolled_at' => 'date', 'status' => EnrollmentStatus::class, 'completed_at' => 'datetime'];
    }
}
