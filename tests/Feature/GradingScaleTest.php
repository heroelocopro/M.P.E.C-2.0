<?php

use App\Enums\UserRole;
use App\Models\AcademicTask;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationAttempt;
use App\Models\Grade;
use App\Models\GradingScale;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TaskGrade;
use App\Models\TeacherAssignment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the school administrator configures a common scale and preserves existing grade equivalence', function () {
    [$teacher, $task, $enrollment] = createScaleWorkspace();
    TaskGrade::query()->create(['academic_task_id' => $task->id, 'enrollment_id' => $enrollment->id, 'graded_by_id' => $teacher->id, 'score' => 4]);
    $evaluation = Evaluation::factory()->create(['teacher_assignment_id' => TeacherAssignment::query()->sole()->id]);
    $attempt = EvaluationAttempt::factory()->create(['evaluation_id' => $evaluation->id, 'user_id' => $enrollment->student->user_id, 'score' => 80]);
    $admin = User::factory()->withRole(UserRole::Administrator)->create();

    $this->actingAs($admin)->put(route('admin.grading-scale.update'), ['minimum' => 0, 'maximum' => 100, 'passing' => 60])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('grading_scales', ['id' => 1, 'minimum' => 0, 'maximum' => 100, 'passing' => 60]);
    $this->assertDatabaseHas('task_grades', ['academic_task_id' => $task->id, 'score' => 80]);
    $this->get(route('admin.academic-settings'))->assertInertia(fn (Assert $page) => $page->where('gradingScale.maximum', 100)->where('gradingScale.passing', 60));
    $this->actingAs($enrollment->student->user)->get(route('evaluations.show', $evaluation))->assertInertia(fn (Assert $page) => $page->where('attempt.score', 80));
    expect($attempt->fresh()->score)->toBe(80.0);
});

test('grade report uses the institutional maximum and passing score', function () {
    $this->view('pdf.grade-report', [
        'institution_name' => 'Colegio',
        'period' => (object) ['name' => 'Periodo 1'],
        'gradingScale' => ['minimum' => 1, 'maximum' => 10, 'passing' => 7],
        'report' => ['year' => '2026', 'student' => 'Estudiante', 'grade' => 'Sexto', 'average' => 6.5, 'subjects' => [
            ['name' => 'Ciencias', 'activities' => 1, 'score' => 6],
            ['name' => 'Lenguaje', 'activities' => 1, 'score' => 7],
        ]],
    ])->assertSee('/ 10,00')->assertSee('class="score fail">6,00', false)->assertSee('class="score pass">7,00', false);
});

test('only administrators can change the grading scale', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();
    $this->actingAs($user)->put(route('admin.grading-scale.update'), ['minimum' => 1, 'maximum' => 10, 'passing' => 6])->assertForbidden();
    expect(GradingScale::current()->maximum)->toBe(5.0);
})->with([UserRole::Student, UserRole::Teacher, UserRole::Guardian]);

test('school scale bounds and passing grade must be coherent', function (array $data, string $error) {
    $admin = User::factory()->withRole(UserRole::Administrator)->create();
    $this->actingAs($admin)->put(route('admin.grading-scale.update'), $data)->assertSessionHasErrors($error);
    expect(GradingScale::current()->maximum)->toBe(5.0);
})->with([
    'equal bounds' => [['minimum' => 5, 'maximum' => 5, 'passing' => 5], 'maximum'],
    'inverted bounds' => [['minimum' => 10, 'maximum' => 5, 'passing' => 5], 'maximum'],
    'passing above maximum' => [['minimum' => 1, 'maximum' => 5, 'passing' => 6], 'passing'],
    'passing below minimum' => [['minimum' => 1, 'maximum' => 5, 'passing' => 0], 'passing'],
    'negative minimum' => [['minimum' => -1, 'maximum' => 5, 'passing' => 3], 'minimum'],
    'too precise' => [['minimum' => 1.001, 'maximum' => 5, 'passing' => 3], 'minimum'],
]);

test('tasks and projects enforce the school minimum and maximum', function (string $type) {
    [$teacher, $task, $enrollment] = createScaleWorkspace();
    $task->update(['type' => $type]);
    GradingScale::current()->update(['minimum' => 1, 'maximum' => 10, 'passing' => 6]);
    $assignment = TeacherAssignment::query()->sole();
    foreach ([0, 10.01] as $invalid) {
        $this->actingAs($teacher)->post(route('teacher.grades.store'), ['assignment_id' => $assignment->id, 'grades' => [$task->id => [$enrollment->id => $invalid]]])->assertSessionHasErrors('grades.'.$task->id.'.'.$enrollment->id);
    }
    foreach ([1, 10] as $valid) {
        $this->post(route('teacher.grades.store'), ['assignment_id' => $assignment->id, 'grades' => [$task->id => [$enrollment->id => $valid]]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('task_grades', ['academic_task_id' => $task->id, 'score' => $valid]);
    }
})->with(['tarea', 'proyecto']);

test('automatic evaluation results respect a nonzero school minimum', function (array $answers, int $expected) {
    [$teacher, , $enrollment] = createScaleWorkspace();
    GradingScale::current()->update(['minimum' => 1, 'maximum' => 5, 'passing' => 3]);
    $question = ['type' => 'choice', 'prompt' => '2 + 2', 'options' => ['3', '4', '5', '6'], 'correct' => 1];
    $evaluation = Evaluation::factory()->create(['teacher_assignment_id' => TeacherAssignment::query()->sole()->id, 'questions' => [$question, $question]]);

    $this->actingAs($enrollment->student->user)->post(route('evaluations.submit', $evaluation), ['answers' => $answers])->assertRedirect()->assertSessionHasNoErrors();

    $this->get(route('evaluations.show', $evaluation))->assertInertia(fn (Assert $page) => $page->where('attempt.score', $expected));
    $this->get(route('evaluations.index'))->assertInertia(fn (Assert $page) => $page->where('evaluations.data.0.attempts.0.score', $expected));
    $this->actingAs($teacher)->get(route('evaluations.show', $evaluation))->assertInertia(fn (Assert $page) => $page->where('results.data.0.score', $expected));
})->with([
    'no correct answers' => [[0, 0], 1],
    'half correct' => [[1, 0], 3],
    'all correct' => [[1, 1], 5],
]);

test('teachers grade open responses in the institutional scale and mixed scores use the same scale', function () {
    [$teacher, , $enrollment] = createScaleWorkspace();
    GradingScale::current()->update(['minimum' => 1, 'maximum' => 5, 'passing' => 3]);
    $evaluation = Evaluation::factory()->create(['teacher_assignment_id' => TeacherAssignment::query()->sole()->id, 'questions' => [
        ['type' => 'choice', 'prompt' => '2 + 2', 'options' => ['3', '4', '5', '6'], 'correct' => 1],
        ['type' => 'open', 'prompt' => 'Explica'],
    ]]);
    $this->actingAs($enrollment->student->user)->post(route('evaluations.submit', $evaluation), ['answers' => [1, 'Mi explicación']])->assertRedirect();
    $attempt = $evaluation->attempts()->sole();
    $this->actingAs($teacher)->patch(route('evaluations.grade', [$evaluation, $attempt]), ['manual_scores' => [1 => 0]])->assertSessionHasErrors('manual_scores.1');
    $this->patch(route('evaluations.grade', [$evaluation, $attempt]), ['manual_scores' => [1 => 6]])->assertSessionHasErrors('manual_scores.1');

    $this->patch(route('evaluations.grade', [$evaluation, $attempt]), ['manual_scores' => [1 => 3]])->assertRedirect()->assertSessionHasNoErrors();

    $this->get(route('evaluations.show', $evaluation))->assertInertia(fn (Assert $page) => $page->where('results.data.0.score', 4)->where('results.data.0.manual_scores.1', 3));
    $this->actingAs($enrollment->student->user)->get(route('evaluations.show', $evaluation))->assertInertia(fn (Assert $page) => $page->where('attempt.score', 4));
});

test('changing only approval leaves stored activity grades unchanged', function () {
    [$teacher, $task, $enrollment] = createScaleWorkspace();
    $grade = TaskGrade::query()->create(['academic_task_id' => $task->id, 'enrollment_id' => $enrollment->id, 'graded_by_id' => $teacher->id, 'score' => 4.25]);
    $admin = User::factory()->withRole(UserRole::Administrator)->create();

    $this->actingAs($admin)->put(route('admin.grading-scale.update'), ['minimum' => 0, 'maximum' => 5, 'passing' => 4])->assertRedirect();

    expect($grade->fresh()->score)->toBe('4.25');
    $this->actingAs($teacher)->get(route('teacher.grades'))->assertInertia(fn (Assert $page) => $page->where('gradingScale.passing', 4));
});

/** @return array{User, AcademicTask, Enrollment} */
function createScaleWorkspace(): array
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
