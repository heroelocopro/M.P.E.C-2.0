<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\ScheduleEntry;
use App\Models\SchoolShift;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('teachers see only their own weekly schedule', function () {
    [$teacher, $classroom, $subject] = createTeacherScheduleWorkspace();
    $otherTeacher = User::factory()->withRole(UserRole::Teacher)->create();
    ScheduleEntry::factory()->create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'day_of_week' => 1,
        'starts_at' => '07:00',
        'ends_at' => '08:00',
        'room' => 'Aula 601',
    ]);
    ScheduleEntry::factory()->create([
        'teacher_id' => $otherTeacher->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
    ]);

    $response = $this->actingAs($teacher)->get(route('teacher.schedule'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('teacher/schedule')
        ->has('entries', 1)
        ->where('entries.0.subject', $subject->name)
        ->where('entries.0.course', 'Sexto A')
        ->where('entries.0.room', 'Aula 601'));
});

test('roles other than teacher cannot view the teacher schedule', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)->get(route('teacher.schedule'))->assertForbidden();
})->with([
    'administrator' => UserRole::Administrator,
    'student' => UserRole::Student,
    'guardian' => UserRole::Guardian,
]);

/** @return array{User, Classroom, Subject} */
function createTeacherScheduleWorkspace(): array
{
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();
    $year = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true]);
    $grade = Grade::factory()->create(['name' => 'Sexto']);
    $campus = Campus::query()->create(['name' => 'Sede Principal', 'code' => 'PRI', 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => 'Mañana', 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
    $classroom = Classroom::query()->create([
        'campus_id' => $campus->id,
        'school_shift_id' => $shift->id,
        'grade_id' => $grade->id,
        'academic_year_id' => $year->id,
        'name' => 'A',
        'capacity' => 35,
        'is_active' => true,
    ]);
    $subject = Subject::query()->create(['name' => 'Matemáticas', 'code' => 'MAT', 'is_active' => true]);
    TeacherAssignment::query()->create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
    ]);

    return [$teacher, $classroom, $subject];
}
