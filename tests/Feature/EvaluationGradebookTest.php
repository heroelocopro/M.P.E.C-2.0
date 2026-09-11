<?php

use App\Actions\SyncEvaluationGrades;
use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a completed evaluation appears read only in teacher grades and contributes to student reports', function () {
    [$teacher, $student, $enrollment, $assignment, $period] = createEvaluationGradebookWorkspace();
    $evaluation = Evaluation::factory()->create(['teacher_assignment_id' => $assignment->id, 'questions' => [
        ['type' => 'choice', 'prompt' => '2 + 2', 'options' => ['3', '4'], 'correct' => 1],
    ]]);
    app(SyncEvaluationGrades::class)->handle($evaluation, $period->id);

    $this->actingAs($student)->post(route('evaluations.submit', $evaluation), ['answers' => [1]])
        ->assertRedirect()->assertSessionHasNoErrors();

    $evaluation = $evaluation->fresh();
    $this->assertDatabaseHas('task_grades', ['academic_task_id' => $evaluation->academic_task_id, 'enrollment_id' => $enrollment->id, 'score' => 5]);
    $this->actingAs($teacher)->get(route('teacher.grades', ['assignment' => $assignment->id, 'period' => $period->id]))
        ->assertInertia(fn (Assert $page) => $page->where('tasks.0.type', 'evaluacion')->where('students.0.grades.'.$evaluation->academic_task_id, '5.00')->where('students.0.final_grade', 5));
    $this->actingAs($student)->get(route('student.grades', ['period' => $period->id]))
        ->assertInertia(fn (Assert $page) => $page->where('report.subjects.0.score', 5)->where('report.subjects.0.activities', 1));
});

/** @return array{User, User, Enrollment, TeacherAssignment, AcademicPeriod} */
function createEvaluationGradebookWorkspace(): array
{
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();
    $studentUser = User::factory()->withRole(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();
    $year = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true]);
    $period = AcademicPeriod::factory()->create(['academic_year_id' => $year->id, 'number' => 1]);
    $grade = Grade::factory()->create(['name' => 'Sexto']);
    $campus = Campus::query()->create(['name' => 'Principal', 'code' => 'PRI', 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => 'Mañana', 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
    $classroom = Classroom::query()->create(['campus_id' => $campus->id, 'school_shift_id' => $shift->id, 'grade_id' => $grade->id, 'academic_year_id' => $year->id, 'name' => 'A', 'capacity' => 35, 'is_active' => true]);
    $enrollment = Enrollment::factory()->for($student)->for($year)->for($grade)->create(['classroom_id' => $classroom->id]);
    $subject = Subject::query()->create(['name' => 'Matemáticas', 'code' => 'MAT', 'is_active' => true]);
    $assignment = TeacherAssignment::query()->create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id]);

    return [$teacher, $studentUser, $enrollment, $assignment, $period];
}
