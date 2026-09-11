<?php

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AcademicTask;
use App\Models\AcademicYear;
use App\Models\AcademicYearResult;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\PeriodFinalGrade;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TaskGrade;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\SchoolAnnouncement;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

test('period closure reminders and final year graduation are processed automatically', function () {
    Notification::fake();
    Mail::fake();
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();
    $studentUser = User::factory()->withRole(UserRole::Student)->create();
    $guardian = User::factory()->withRole(UserRole::Guardian)->create();
    $student = Student::factory()->for($studentUser)->create();
    $student->guardians()->attach($guardian);
    $year = AcademicYear::factory()->create(['is_active' => true, 'ends_at' => '2026-11-30']);
    $period = AcademicPeriod::factory()->for($year)->create(['number' => 1, 'starts_at' => '2026-09-01', 'ends_at' => '2026-09-30', 'is_closed' => false]);
    $grade = Grade::factory()->create(['name' => 'Undécimo', 'code' => '11', 'is_final' => true]);
    $campus = Campus::query()->create(['name' => 'Principal', 'code' => 'PRI', 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => 'Mañana', 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
    $classroom = Classroom::query()->create(['campus_id' => $campus->id, 'school_shift_id' => $shift->id, 'grade_id' => $grade->id, 'academic_year_id' => $year->id, 'name' => 'A', 'capacity' => 35, 'is_active' => true]);
    $enrollment = Enrollment::factory()->for($student)->for($year)->for($grade)->create(['classroom_id' => $classroom->id, 'status' => EnrollmentStatus::Active]);
    $subject = Subject::query()->create(['name' => 'Matemáticas', 'code' => 'MAT', 'is_active' => true]);
    $assignment = TeacherAssignment::query()->create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id]);
    $task = AcademicTask::factory()->for($teacher, 'assignedBy')->for($subject)->create(['classroom_id' => $classroom->id, 'academic_period_id' => $period->id]);
    TaskGrade::factory()->for($task)->for($enrollment)->create(['graded_by_id' => $teacher->id, 'score' => 4.2]);

    $this->artisan('academics:process-closures', ['--date' => '2026-09-27'])->assertSuccessful();
    expect($period->refresh()->is_closed)->toBeFalse();
    Notification::assertSentTo([$administrator, $teacher, $studentUser, $guardian], SchoolAnnouncement::class);

    $this->artisan('academics:process-closures', ['--date' => '2026-09-30'])->assertSuccessful();

    expect($period->refresh()->is_closed)->toBeTrue()
        ->and(PeriodFinalGrade::query()->whereBelongsTo($enrollment)->sole()->final_score)->toBe(4.2)
        ->and(PeriodFinalGrade::query()->sole()->activity_breakdown)->toHaveCount(1)
        ->and(AcademicYearResult::query()->whereBelongsTo($enrollment)->sole()->passed)->toBeTrue()
        ->and($enrollment->refresh()->status)->toBe(EnrollmentStatus::Completed)
        ->and($student->refresh()->status)->toBe(StudentStatus::Graduated)
        ->and($year->refresh()->is_active)->toBeFalse();
    Mail::assertSentCount(2);

    $this->actingAs($teacher)->post(route('teacher.grades.store'), [
        'assignment_id' => $assignment->id,
        'period_id' => $period->id,
        'grades' => [$task->id => [$enrollment->id => 5]],
    ])->assertForbidden();
    expect(TaskGrade::query()->whereBelongsTo($task, 'academicTask')->sole()->score)->toBe('4.20');
});

test('academic closure processing is idempotent', function () {
    Notification::fake();

    $year = AcademicYear::factory()->create(['is_active' => true]);
    $period = AcademicPeriod::factory()->for($year)->create(['ends_at' => '2026-09-30']);

    $this->artisan('academics:process-closures', ['--date' => '2026-09-27'])->assertSuccessful();
    $this->artisan('academics:process-closures', ['--date' => '2026-09-27'])->assertSuccessful();

    $this->assertDatabaseCount('academic_period_reminders', 1);
});
