<?php

use App\Enums\UserRole;
use App\Models\AcademicTask;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TaskGrade;
use App\Models\TeacherAssignment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('teachers see their activities and enrolled students in the gradebook', function () {
    [$teacher, $task, $enrollment] = createGradebookWorkspace();
    $assignment = TeacherAssignment::query()->where('teacher_id', $teacher->id)->sole();

    $response = $this->actingAs($teacher)->get(route('teacher.grades', ['assignment' => $assignment->id]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('teacher/grades')
        ->has('tasks', 1)
        ->where('tasks.0.title', 'Proyecto de ciencias')
        ->where('selected_assignment_id', $assignment->id)
        ->has('students', 1)
        ->where('students.0.enrollment_id', $enrollment->id));
});

test('teachers create and update a grade for their task', function () {
    [$teacher, $task, $enrollment] = createGradebookWorkspace();
    $assignment = TeacherAssignment::query()->where('teacher_id', $teacher->id)->sole();

    $this->actingAs($teacher)->post(route('teacher.grades.store'), [
        'assignment_id' => $assignment->id,
        'grades' => [
            $task->id => [$enrollment->id => '4.25'],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('task_grades', [
        'academic_task_id' => $task->id,
        'enrollment_id' => $enrollment->id,
        'graded_by_id' => $teacher->id,
        'score' => 4.25,
    ]);

    $this->actingAs($teacher)->post(route('teacher.grades.store'), [
        'assignment_id' => $assignment->id,
        'grades' => [
            $task->id => [$enrollment->id => '3.80'],
        ],
    ])->assertRedirect();

    expect(TaskGrade::query()->whereBelongsTo($task)->get())
        ->toHaveCount(1)
        ->first()->score->toBe('3.80');
});

test('a teacher cannot grade another teachers task', function () {
    [, $task, $enrollment] = createGradebookWorkspace();
    $otherTeacher = User::factory()->withRole(UserRole::Teacher)->create();
    $assignment = TeacherAssignment::query()->sole();

    $this->actingAs($otherTeacher)->post(route('teacher.grades.store'), [
        'assignment_id' => $assignment->id,
        'grades' => [$task->id => [$enrollment->id => '5']],
    ])->assertForbidden();

    expect(TaskGrade::query()->exists())->toBeFalse();
});

test('a teacher cannot grade an enrollment outside the task classroom', function () {
    [$teacher, $task] = createGradebookWorkspace();
    $assignment = TeacherAssignment::query()->where('teacher_id', $teacher->id)->sole();
    $outsideEnrollment = Enrollment::factory()->create();

    $this->actingAs($teacher)->post(route('teacher.grades.store'), [
        'assignment_id' => $assignment->id,
        'grades' => [$task->id => [$outsideEnrollment->id => '4']],
    ])->assertUnprocessable();

    expect(TaskGrade::query()->exists())->toBeFalse();
});

test('grades must be between zero and five', function () {
    [$teacher, $task, $enrollment] = createGradebookWorkspace();
    $assignment = TeacherAssignment::query()->where('teacher_id', $teacher->id)->sole();

    $this->actingAs($teacher)->post(route('teacher.grades.store'), [
        'assignment_id' => $assignment->id,
        'grades' => [$task->id => [$enrollment->id => '5.1']],
    ])->assertSessionHasErrors([
        "grades.{$task->id}.{$enrollment->id}" => 'La nota debe estar entre 0,00 y 5,00.',
    ]);

    expect(TaskGrade::query()->exists())->toBeFalse();
});

/** @return array{User, AcademicTask, Enrollment} */
function createGradebookWorkspace(): array
{
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();
    $studentUser = User::factory()->withRole(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();
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
    $enrollment = Enrollment::factory()->for($student)->for($year)->for($grade)->create(['classroom_id' => $classroom->id]);
    $subject = Subject::query()->create(['name' => 'Ciencias', 'code' => 'CIE', 'is_active' => true]);
    TeacherAssignment::query()->create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id]);
    $task = AcademicTask::factory()->create([
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'assigned_by_id' => $teacher->id,
        'title' => 'Proyecto de ciencias',
        'type' => 'proyecto',
        'published_at' => now(),
    ]);

    return [$teacher, $task, $enrollment];
}
