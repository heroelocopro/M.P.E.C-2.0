<?php

use App\Enums\UserRole;
use App\Models\AcademicTask;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationAttempt;
use App\Models\ForumTopic;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('students filter available expired and all published activities', function (string $status, array $titles) {
    [$teacher, $classroom, $subject, $user] = createLearningWorkspace();
    $this->travelTo(now()->startOfSecond());
    foreach (['Disponible' => now()->addDay(), 'Vencida' => now()->subDay(), 'Limite' => now()] as $title => $dueAt) {
        AcademicTask::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'assigned_by_id' => $teacher->id, 'title' => $title, 'due_at' => $dueAt]);
    }
    AcademicTask::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'assigned_by_id' => $teacher->id, 'published_at' => now()->addDay()]);

    $this->actingAs($user)->get(route('student.tasks', $status ? ['status' => $status] : []))
        ->assertInertia(fn (Assert $page) => $page->has('tasks', count($titles))->where('tasks', fn ($tasks) => $tasks->pluck('title')->all() === $titles));
})->with([
    'default' => ['', ['Limite', 'Disponible']],
    'expired' => ['expired', ['Vencida']],
    'all' => ['all', ['Vencida', 'Limite', 'Disponible']],
    'invalid' => ['invalid', ['Limite', 'Disponible']],
]);

test('teachers extend their own activity deadlines and students see the reopened activity', function () {
    [$teacher, $classroom, $subject, $student] = createLearningWorkspace();
    $task = AcademicTask::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'assigned_by_id' => $teacher->id, 'due_at' => now()->subDay()]);
    $date = now()->addWeek()->startOfSecond();

    $this->actingAs($teacher)->patch(route('teacher.tasks.update', $task), ['due_at' => $date->toIso8601String(), 'title' => 'Unauthorized title change'])->assertRedirect()->assertSessionHasNoErrors();

    expect($task->fresh()->due_at->equalTo($date))->toBeTrue();
    expect($task->fresh()->title)->toBe($task->title);
    $this->actingAs($student)->get(route('student.tasks'))->assertInertia(fn (Assert $page) => $page->has('tasks', 1));
});

test('deadline changes reject other teachers and invalid dates', function () {
    [$teacher, $classroom, $subject] = createLearningWorkspace();
    $task = AcademicTask::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'assigned_by_id' => $teacher->id]);
    $other = User::factory()->withRole(UserRole::Teacher)->create();
    $this->actingAs($other)->patch(route('teacher.tasks.update', $task), ['due_at' => now()->addWeek()])->assertForbidden();
    $this->actingAs($teacher)->patch(route('teacher.tasks.update', $task), ['due_at' => 'invalid'])->assertSessionHasErrors('due_at');
    $this->actingAs($teacher)->patch(route('teacher.tasks.update', $task), ['due_at' => now()->subDay()])->assertSessionHasErrors('due_at');
    $teacher->teacherAssignments()->delete();
    $this->actingAs($teacher)->patch(route('teacher.tasks.update', $task), ['due_at' => now()->addWeek()])->assertForbidden();
});

test('students start forum topics and their teacher can reply', function () {
    [$teacher, $classroom, , $student] = createLearningWorkspace();
    $this->actingAs($student)->post(route('forum.store'), ['classroom_id' => $classroom->id, 'title' => 'Dudas', 'body' => '¿Cómo resolvemos el ejercicio?'])->assertRedirect();
    $topic = ForumTopic::query()->firstOrFail();
    $this->assertDatabaseHas('forum_topics', ['id' => $topic->id, 'user_id' => $student->id, 'title' => 'Dudas']);
    $this->actingAs($teacher)->post(route('forum.reply', $topic), ['body' => 'Revisemos los pasos.'])->assertRedirect();
    $this->assertDatabaseHas('forum_replies', ['forum_topic_id' => $topic->id, 'user_id' => $teacher->id, 'body' => 'Revisemos los pasos.']);
    $this->actingAs($student)->get(route('forum.show', $topic))->assertInertia(fn (Assert $page) => $page->has('replies.data', 1));
    $this->get(route('forum.index'))->assertInertia(fn (Assert $page) => $page->has('topics.data', 1));
});

test('forum access is restricted to course participants and messages are validated', function () {
    [$teacher, $classroom, , $student] = createLearningWorkspace();
    $topic = ForumTopic::factory()->create(['classroom_id' => $classroom->id, 'user_id' => $teacher->id]);
    $outsider = User::factory()->withRole(UserRole::Student)->create();
    $this->actingAs($outsider)->get(route('forum.index'))->assertInertia(fn (Assert $page) => $page->has('topics.data', 0));
    $this->get(route('forum.show', $topic))->assertForbidden();
    $this->post(route('forum.reply', $topic), ['body' => 'Intrusion'])->assertForbidden();
    $this->post(route('forum.store'), ['classroom_id' => $classroom->id, 'title' => 'Intrusion', 'body' => 'Intrusion'])->assertForbidden();
    $this->actingAs($student)->post(route('forum.reply', $topic), ['body' => ' '])->assertSessionHasErrors('body');
    $this->post(route('forum.store'), ['classroom_id' => $classroom->id])->assertSessionHasErrors(['title', 'body']);
    $this->assertDatabaseCount('forum_replies', 0);
});

test('teachers publish mixed evaluations without exposing answer keys to students', function () {
    [$teacher, , , $student] = createLearningWorkspace();
    $assignment = $teacher->teacherAssignments()->firstOrFail();
    $this->actingAs($teacher)->post(route('evaluations.store'), [
        'teacher_assignment_id' => $assignment->id, 'title' => 'Evaluación mixta', 'due_at' => now()->addDay(),
        'questions' => [
            ['type' => 'choice', 'prompt' => '2 + 2', 'options' => ['3', '4', '5', '6'], 'correct' => 1],
            ['type' => 'open', 'prompt' => 'Explica tu respuesta.', 'options' => ['', '', '', ''], 'correct' => 0],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();
    $evaluation = Evaluation::query()->firstOrFail();
    $this->assertDatabaseHas('evaluations', ['id' => $evaluation->id, 'title' => 'Evaluación mixta']);
    $this->actingAs($student)->get(route('evaluations.show', $evaluation))->assertInertia(fn (Assert $page) => $page->has('questions', 2)->missing('questions.0.correct')->missing('evaluation.questions')->where('isTeacher', false));
    $this->get(route('evaluations.index'))->assertInertia(fn (Assert $page) => $page->has('evaluations.data', 1)->missing('evaluations.data.0.questions'));
});

test('publishing an evaluation notifies active students in its classroom with a direct link', function () {
    [$teacher, $classroom, $subject, $student] = createLearningWorkspace();
    $inactiveUser = User::factory()->withRole(UserRole::Student)->create();
    $inactiveStudent = Student::factory()->for($inactiveUser)->create();
    Enrollment::factory()->create([
        'student_id' => $inactiveStudent->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $classroom->academic_year_id,
        'grade_id' => $classroom->grade_id,
        'status' => 'retirada',
    ]);
    $outsideUser = User::factory()->withRole(UserRole::Student)->create();
    $outsideStudent = Student::factory()->for($outsideUser)->create();
    $otherClassroom = Classroom::query()->create([...$classroom->only(['campus_id', 'school_shift_id', 'grade_id', 'academic_year_id', 'capacity', 'is_active']), 'name' => 'B']);
    Enrollment::factory()->create([
        'student_id' => $outsideStudent->id,
        'classroom_id' => $otherClassroom->id,
        'academic_year_id' => $classroom->academic_year_id,
        'grade_id' => $classroom->grade_id,
    ]);

    $this->actingAs($teacher)->post(route('evaluations.store'), [
        'teacher_assignment_id' => $teacher->teacherAssignments()->sole()->id,
        'title' => 'Evaluación del curso',
        'due_at' => now()->addDay(),
        'questions' => [['type' => 'open', 'prompt' => 'Explica lo aprendido.']],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $evaluation = Evaluation::query()->sole();
    $notification = $student->unreadNotifications()->sole();
    expect($notification->data)
        ->toMatchArray([
            'title' => 'Nueva evaluación: Evaluación del curso',
            'category' => 'evaluacion',
            'url' => route('evaluations.show', $evaluation, absolute: false),
        ]);
    expect($notification->data['message'])->toContain($subject->name)->toContain('Vence');
    expect($inactiveUser->notifications()->count())->toBe(0);
    expect($outsideUser->notifications()->count())->toBe(0);
    expect($teacher->notifications()->count())->toBe(0);
    $this->actingAs($student)->get(route('notifications'))->assertInertia(fn (Assert $page) => $page
        ->where('notificationCenter.unread_count', 1)
        ->where('notifications.data.0.category', 'evaluacion')
        ->where('notifications.data.0.url', route('evaluations.show', $evaluation, absolute: false)));
    $this->get($notification->data['url'])->assertOk();
});

test('invalid or unauthorized evaluation publication does not notify students', function () {
    [$teacher, , , $student] = createLearningWorkspace();
    $assignment = $teacher->teacherAssignments()->sole();
    $this->actingAs($teacher)->post(route('evaluations.store'), ['teacher_assignment_id' => $assignment->id])
        ->assertSessionHasErrors(['title', 'due_at', 'questions']);
    $this->actingAs($student)->post(route('evaluations.store'), ['teacher_assignment_id' => $assignment->id])
        ->assertForbidden();

    $this->assertDatabaseCount('evaluations', 0);
    expect($student->notifications()->count())->toBe(0);
});

test('mixed responses await teacher review and calculate the final weighted result', function () {
    [$teacher, , , $student] = createLearningWorkspace();
    $evaluation = Evaluation::factory()->create(['teacher_assignment_id' => $teacher->teacherAssignments()->firstOrFail()->id, 'questions' => [
        ['type' => 'choice', 'prompt' => '2 + 2', 'options' => ['3', '4', '5', '6'], 'correct' => 1],
        ['type' => 'open', 'prompt' => 'Explica'],
    ]]);
    $this->actingAs($student)->post(route('evaluations.submit', $evaluation), ['answers' => [1, 'Sumo dos unidades a dos.'], 'score' => 100])->assertRedirect()->assertSessionHasNoErrors();
    $attempt = $evaluation->attempts()->firstOrFail();
    expect($attempt->graded_at)->toBeNull();
    expect($attempt->score)->toBe(50.0);
    $this->actingAs($teacher)->patch(route('evaluations.grade', [$evaluation, $attempt]), ['manual_scores' => [1 => 3], 'feedback' => 'Desarrolla la explicación.'])->assertRedirect()->assertSessionHasNoErrors();
    expect($attempt->fresh()->score)->toBe(80.0);
    expect($attempt->fresh()->graded_at)->not->toBeNull();
    $this->actingAs($student)->get(route('evaluations.show', $evaluation))->assertInertia(fn (Assert $page) => $page->where('attempt.score', 4)->where('attempt.feedback', 'Desarrolla la explicación.')->where('results', null));
});

test('automatic evaluations grade once and reject expired or malformed submissions', function () {
    [$teacher, , , $student] = createLearningWorkspace();
    $evaluation = Evaluation::factory()->create(['teacher_assignment_id' => $teacher->teacherAssignments()->firstOrFail()->id]);
    $this->actingAs($student)->post(route('evaluations.submit', $evaluation), ['answers' => [9]])->assertSessionHasErrors('answers.0');
    $this->post(route('evaluations.submit', $evaluation), ['answers' => []])->assertSessionHasErrors('answers');
    $this->post(route('evaluations.submit', $evaluation), ['answers' => [1]])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('evaluation_attempts', ['evaluation_id' => $evaluation->id, 'user_id' => $student->id, 'score' => 100]);
    $this->post(route('evaluations.submit', $evaluation), ['answers' => [0]])->assertSessionHasErrors('answers');
    $evaluation->update(['due_at' => now()->subDay()]);
    $this->post(route('evaluations.submit', $evaluation), ['answers' => [1]])->assertSessionHasErrors('answers');
    $this->assertDatabaseCount('evaluation_attempts', 1);
});

test('evaluation endpoints reject unauthorized participants and invalid questions', function () {
    [$teacher, , , $student] = createLearningWorkspace();
    $assignment = $teacher->teacherAssignments()->firstOrFail();
    $evaluation = Evaluation::factory()->create(['teacher_assignment_id' => $assignment->id]);
    $otherTeacher = User::factory()->withRole(UserRole::Teacher)->create();
    $outsider = User::factory()->withRole(UserRole::Student)->create();
    foreach ([$otherTeacher, $outsider] as $user) {
        $this->actingAs($user)->get(route('evaluations.show', $evaluation))->assertForbidden();
        $this->post(route('evaluations.store'), ['teacher_assignment_id' => $assignment->id])->assertForbidden();
        $this->post(route('evaluations.submit', $evaluation), ['answers' => [1]])->assertForbidden();
    }
    $this->actingAs($teacher)->post(route('evaluations.store'), ['teacher_assignment_id' => $assignment->id, 'title' => 'Invalid', 'due_at' => now()->addDay(), 'questions' => [['type' => 'choice', 'prompt' => 'Test', 'options' => ['Only one'], 'correct' => 7]]])->assertSessionHasErrors(['questions.0.options', 'questions.0.correct']);
    $this->assertDatabaseCount('evaluations', 1);
});

test('only the evaluation owner can grade its attempts and scores must be valid', function () {
    [$teacher, , , $student] = createLearningWorkspace();
    $evaluation = Evaluation::factory()->create(['teacher_assignment_id' => $teacher->teacherAssignments()->firstOrFail()->id, 'questions' => [['type' => 'open', 'prompt' => 'Explica']]]);
    $attempt = EvaluationAttempt::factory()->create(['evaluation_id' => $evaluation->id, 'user_id' => $student->id, 'answers' => ['Respuesta'], 'correct_count' => 0, 'score' => 0, 'graded_at' => null]);
    $this->actingAs($student)->patch(route('evaluations.grade', [$evaluation, $attempt]), ['manual_scores' => [100]])->assertForbidden();
    $otherTeacher = User::factory()->withRole(UserRole::Teacher)->create();
    $this->actingAs($otherTeacher)->patch(route('evaluations.grade', [$evaluation, $attempt]), ['manual_scores' => [100]])->assertForbidden();
    $this->actingAs($teacher)->patch(route('evaluations.grade', [$evaluation, $attempt]), ['manual_scores' => [101]])->assertSessionHasErrors('manual_scores.0');
    $this->patch(route('evaluations.grade', [$evaluation, $attempt]), ['manual_scores' => [0 => 50, 1 => 100]])->assertSessionHasErrors('manual_scores');
    $another = Evaluation::factory()->create(['teacher_assignment_id' => $evaluation->teacher_assignment_id]);
    $this->patch(route('evaluations.grade', [$another, $attempt]), ['manual_scores' => [100]])->assertForbidden();
    expect($attempt->fresh()->graded_at)->toBeNull();
});

test('learning pages require authentication and reject unrelated roles', function () {
    $this->get(route('forum.index'))->assertRedirect(route('login'));
    $this->get(route('evaluations.index'))->assertRedirect(route('login'));
    $user = User::factory()->withRole(UserRole::Guardian)->create();
    $this->actingAs($user)->get(route('forum.index'))->assertForbidden();
    $this->get(route('evaluations.index'))->assertForbidden();
});

/** @return array{User, Classroom, Subject, User, Enrollment} */
function createLearningWorkspace(): array
{
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();
    $studentUser = User::factory()->withRole(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();
    $year = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true]);
    $grade = Grade::factory()->create(['name' => 'Sexto']);
    $campus = Campus::query()->create(['name' => 'Sede Principal', 'code' => 'PRI', 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => 'Mañana', 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
    $classroom = Classroom::query()->create(['campus_id' => $campus->id, 'school_shift_id' => $shift->id, 'grade_id' => $grade->id, 'academic_year_id' => $year->id, 'name' => 'A', 'capacity' => 35, 'is_active' => true]);
    $enrollment = Enrollment::factory()->for($student)->for($year)->for($grade)->create(['classroom_id' => $classroom->id]);
    $subject = Subject::query()->create(['name' => 'Matemáticas', 'code' => 'MAT', 'is_active' => true]);
    TeacherAssignment::query()->create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id]);

    return [$teacher, $classroom, $subject, $studentUser, $enrollment];
}
