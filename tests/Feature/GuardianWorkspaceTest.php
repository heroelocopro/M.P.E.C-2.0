<?php

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AcademicTask;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\Subject;
use App\Models\TaskGrade;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\SchoolAnnouncement;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('guardians see all linked students with attendance totals and reports', function () {
    [$teacher, $student, $enrollment] = createGuardianWorkspace();
    $guardian = User::factory()->withRole(UserRole::Guardian)->create();
    $student->student->guardians()->attach($guardian, ['relationship' => 'Madre']);
    AttendanceRecord::factory()->for($enrollment)->create(['status' => 'ausente', 'recorded_by_id' => $teacher->id]);
    StudentReport::query()->create(['student_id' => $student->student->id, 'reported_by_id' => $teacher->id, 'category' => 'salud', 'title' => 'Se siente mal', 'details' => 'Fue enviado a enfermería.', 'reported_at' => now()]);

    $this->actingAs($guardian)->get(route('guardian.dashboard'))->assertInertia(fn (Assert $page) => $page
        ->component('guardian/dashboard')->has('students', 1)->where('students.0.relationship', 'Madre')
        ->where('students.0.attendance.total', 1)->where('students.0.attendance.absent', 1)
        ->where('students.0.reports.0.title', 'Se siente mal'));
});

test('guardians see the current period and academic year averages for each linked student', function () {
    [$teacher, $student, $enrollment] = createGuardianWorkspace();
    $guardian = User::factory()->withRole(UserRole::Guardian)->create();
    $student->student->guardians()->attach($guardian);
    $subject = Subject::query()->firstOrFail();
    $currentPeriod = AcademicPeriod::factory()->for($enrollment->academicYear)->create([
        'name' => 'Periodo actual', 'number' => 2, 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonth(),
    ]);
    $previousPeriod = AcademicPeriod::factory()->for($enrollment->academicYear)->create([
        'name' => 'Periodo anterior', 'number' => 1, 'starts_at' => now()->subMonths(4), 'ends_at' => now()->subMonths(2), 'is_closed' => true,
    ]);
    $currentTask = AcademicTask::factory()->for($teacher, 'assignedBy')->for($subject)->create([
        'classroom_id' => $enrollment->classroom_id, 'academic_period_id' => $currentPeriod->id,
    ]);
    $previousTask = AcademicTask::factory()->for($teacher, 'assignedBy')->for($subject)->create([
        'classroom_id' => $enrollment->classroom_id, 'academic_period_id' => $previousPeriod->id,
    ]);
    TaskGrade::factory()->for($currentTask)->for($enrollment)->create(['graded_by_id' => $teacher->id, 'score' => 4.5]);
    TaskGrade::factory()->for($previousTask)->for($enrollment)->create(['graded_by_id' => $teacher->id, 'score' => 3.5]);

    $this->actingAs($guardian)->get(route('guardian.dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('students.0.grades.current_period', 'Periodo actual')
        ->where('students.0.grades.current_average', 4.5)
        ->where('students.0.grades.year_average', 4));
});

test('non guardians cannot access the guardian workspace', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();
    $this->actingAs($user)->get(route('guardian.dashboard'))->assertForbidden();
})->with([UserRole::Administrator, UserRole::Teacher, UserRole::Student]);

test('attendance incidents and student reports notify student and guardians', function () {
    [$teacher, $student, $enrollment] = createGuardianWorkspace();
    $guardian = User::factory()->withRole(UserRole::Guardian)->create();
    $student->student->guardians()->attach($guardian);
    Notification::fake();

    $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
        'classroom_id' => $enrollment->classroom_id,
        'date' => now()->toDateString(),
        'attendance' => [$enrollment->id => 'ausente'],
        'notes' => [$enrollment->id => 'No asistió'],
    ])->assertRedirect();
    $this->post(route('teacher.student-reports.store', $student->student), [
        'category' => 'salud', 'title' => 'Se siente mal', 'details' => 'Fue llevado a enfermería.',
    ])->assertRedirect();

    Notification::assertSentTo([$student, $guardian], SchoolAnnouncement::class);
    $this->assertDatabaseHas('student_reports', ['student_id' => $student->student->id, 'title' => 'Se siente mal']);
});

/** @return array{User, User, Enrollment} */
function createGuardianWorkspace(): array
{
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();
    $studentUser = User::factory()->withRole(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $grade = Grade::factory()->create();
    $campus = Campus::query()->create(['name' => 'Principal', 'code' => 'PRI', 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => 'Mañana', 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
    $classroom = Classroom::query()->create(['campus_id' => $campus->id, 'school_shift_id' => $shift->id, 'grade_id' => $grade->id, 'academic_year_id' => $year->id, 'name' => 'A', 'capacity' => 35, 'is_active' => true]);
    $enrollment = Enrollment::factory()->for($student)->for($year)->for($grade)->create(['classroom_id' => $classroom->id]);
    $subject = Subject::query()->create(['name' => 'Ciencias', 'code' => 'CIE', 'is_active' => true]);
    TeacherAssignment::query()->create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id]);

    return [$teacher, $studentUser, $enrollment];
}
