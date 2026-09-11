<?php

use App\Enums\UserRole;
use App\Models\AcademicTask;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\ScheduleEntry;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('students see tasks published for their current classroom only', function () {
    [$user, $classroom, $subject, $teacher] = createStudentWorkspace();
    AcademicTask::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'assigned_by_id' => $teacher->id, 'title' => 'Fracciones equivalentes', 'due_at' => '2026-09-20 18:00:00']);
    $otherClassroom = Classroom::query()->create([...$classroom->only(['campus_id', 'school_shift_id', 'grade_id', 'academic_year_id', 'capacity', 'is_active']), 'name' => 'B']);
    AcademicTask::factory()->create(['classroom_id' => $otherClassroom->id, 'subject_id' => $subject->id, 'assigned_by_id' => $teacher->id, 'title' => 'Actividad de otro curso']);

    $response = $this->actingAs($user)->get(route('student.tasks'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('student/tasks')->has('tasks', 1)
        ->where('tasks.0.title', 'Fracciones equivalentes')->where('tasks.0.subject', 'Matemáticas'));
});

test('students see their weekly schedule', function () {
    [$user, $classroom, $subject, $teacher] = createStudentWorkspace();
    ScheduleEntry::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'day_of_week' => 1, 'starts_at' => '07:00', 'ends_at' => '08:00', 'room' => 'Aula 601']);

    $response = $this->actingAs($user)->get(route('student.schedule'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('student/schedule')->where('course', 'Sexto A')
        ->where('entries.0.subject', 'Matemáticas')->where('entries.0.starts_at', '07:00')->where('entries.0.room', 'Aula 601'));
});

test('roles other than student cannot access the student workspace', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)->get(route('student.tasks'))->assertForbidden();
})->with(['administrator' => UserRole::Administrator, 'teacher' => UserRole::Teacher, 'guardian' => UserRole::Guardian]);

/** @return array{User, Classroom, Subject, User} */
function createStudentWorkspace(): array
{
    $user = User::factory()->withRole(UserRole::Student)->create();
    $student = Student::factory()->for($user)->create();
    $year = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true]);
    $grade = Grade::factory()->create(['name' => 'Sexto']);
    $campus = Campus::query()->create(['name' => 'Sede Principal', 'code' => 'PRI', 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => 'Mañana', 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
    $classroom = Classroom::query()->create(['campus_id' => $campus->id, 'school_shift_id' => $shift->id, 'grade_id' => $grade->id, 'academic_year_id' => $year->id, 'name' => 'A', 'capacity' => 35, 'is_active' => true]);
    Enrollment::factory()->for($student)->for($year)->for($grade)->create(['classroom_id' => $classroom->id]);
    $subject = Subject::query()->create(['name' => 'Matemáticas', 'code' => 'MAT', 'is_active' => true]);
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();

    return [$user, $classroom, $subject, $teacher];
}
