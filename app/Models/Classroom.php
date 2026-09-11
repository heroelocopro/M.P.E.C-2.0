<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['campus_id', 'school_shift_id', 'grade_id', 'academic_year_id', 'name', 'capacity', 'is_active'])]
class Classroom extends Model
{
    public function accessibleTo(User $user): bool
    {
        if ($user->role === UserRole::Teacher) {
            return $this->teacherAssignments()->where('teacher_id', $user->id)->exists();
        }

        return $user->role === UserRole::Student
            && $this->enrollments()->where('status', 'activa')
                ->whereHas('student', fn ($query) => $query->where('user_id', $user->id))->exists();
    }

    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    /** @return BelongsTo<Campus, $this> */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /** @return BelongsTo<SchoolShift, $this> */
    public function schoolShift(): BelongsTo
    {
        return $this->belongsTo(SchoolShift::class);
    }

    /** @return BelongsTo<Grade, $this> */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasMany<TeacherAssignment, $this> */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /** @return HasMany<AcademicTask, $this> */
    public function academicTasks(): HasMany
    {
        return $this->hasMany(AcademicTask::class);
    }

    /** @return HasMany<ScheduleEntry, $this> */
    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }

    /** @return BelongsToMany<Announcement, $this> */
    public function announcements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['capacity' => 'integer', 'is_active' => 'boolean'];
    }
}
