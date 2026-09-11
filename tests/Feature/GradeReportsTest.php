<?php

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
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
use App\Services\InstitutionalAccountService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('administrators can inspect period grades', function () {
    [$admin, $student, $period] = createGradeReportWorkspace();

    $this->actingAs($admin)->get(route('admin.grades', ['period' => $period->id]))
        ->assertInertia(fn(Assert $page) => $page
            ->component('admin/grades')
            ->where('selected_period_id', $period->id)
            ->has('students', 1)
            ->where('students.0.name', 'Estudiante Demo'));
});

test('students can download only their own period report', function () {
    [$admin, $student, $period] = createGradeReportWorkspace();

    $response = $this->actingAs($student)->get(route('student.grades.pdf', $period));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('periodo-1-2026.pdf');
    expect($response->getContent())->toStartWith('%PDF');
});
test('students can submit a file that their teacher can download', function () {
    [, $student] = createGradeReportWorkspace();
    $task = AcademicTask::query()->sole();
    $teacher = User::query()->where('role', UserRole::Teacher)->sole();
    Storage::fake('local');

    $this->actingAs($student)->post(route('student.tasks.submit', $task), [
        'file' => UploadedFile::fake()->create('entrega-final.pdf', 250, 'application/pdf'),
    ])->assertRedirect();

    $this->assertDatabaseHas('academic_task_submissions', [
        'academic_task_id' => $task->id,
        'enrollment_id' => $task->classroom->enrollments()->sole()->id,
        'original_name' => 'entrega-final.pdf',
    ]);

    $submission = $task->submissions()->sole();
    $this->actingAs($teacher)->get(route('task-submissions.download', $submission))->assertOk();
});

test('students cannot download a report from another academic year', function () {
    [, $student] = createGradeReportWorkspace();
    $otherYear = AcademicYear::factory()->create(['name' => '2027']);
    $otherPeriod = AcademicPeriod::factory()->create(['academic_year_id' => $otherYear->id, 'number' => 1]);

    $this->actingAs($student)->get(route('student.grades.pdf', $otherPeriod))->assertNotFound();
});

test('administrators can save sensitive student data and register qr entry', function () {
    [$admin, $student] = createGradeReportWorkspace();
    $studentModel = $student->student()->firstOrFail();

    $this->actingAs($admin)->put(route('admin.students.medical.update', $studentModel), [
        'blood_type' => 'O+',
        'allergies' => 'Ninguna conocida',
        'gender_identity' => 'Prefiere no informar',
        'data_consent' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('student_medical_profiles', [
        'student_id' => $studentModel->id,
        'blood_type' => 'O+',
        'data_consent' => true,
    ]);

    $studentModel->update(['card_token' => (string) str()->uuid()]);
    $this->actingAs($admin)->get(route('admin.institutional-attendance.scan', $studentModel->card_token))
        ->assertInertia(fn(Assert $page) => $page
            ->component('admin/institutional-attendance')
            ->where('scanned.document', $studentModel->document_number));

    $this->assertDatabaseHas('institutional_attendances', [
        'student_id' => $studentModel->id,
        'method' => 'qr',
    ]);
});

test('administrators can send a community email', function () {
    [$admin] = createGradeReportWorkspace();
    Mail::fake();

    $this->actingAs($admin)->post(route('admin.communication.send-all'), [
        'subject' => 'Reunión general',
        'message' => 'La reunión será mañana.',
    ])->assertRedirect();
});

test('institutional accounts use the school domain and notify the personal email', function () {
    Mail::fake();
    $user = User::factory()->withRole(UserRole::Teacher)->create(['name' => 'William Andrés Gómez', 'email' => 'pending@example.test']);

    app(InstitutionalAccountService::class)->provision($user, 'william.personal@example.com');
    $user->refresh();

    expect($user->email)->toBe('wag@mpec.com')
        ->and($user->personal_email)->toBe('william.personal@example.com')
        ->and($user->password)->not->toBe('pending');
});

/** @return array{User, User, AcademicPeriod} */
function createGradeReportWorkspace(): array
{
    $admin = User::factory()->withRole(UserRole::Administrator)->create();
    $studentUser = User::factory()->withRole(UserRole::Student)->create();
    $student = Student::factory()->create([
        'user_id' => $studentUser->id,
        'first_name' => 'Estudiante',
        'middle_name' => null,
        'last_name' => 'Demo',
        'second_last_name' => null,
    ]);
    $year = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true]);
    $period = AcademicPeriod::factory()->create([
        'academic_year_id' => $year->id,
        'name' => 'Periodo 1',
        'number' => 1,
        'starts_at' => '2026-01-20',
        'ends_at' => '2026-03-31',
    ]);
    $grade = Grade::factory()->create(['name' => 'Sexto']);
    $campus = Campus::query()->create(['name' => 'Sede Principal', 'code' => 'PRI', 'is_active' => true]);
    $shift = SchoolShift::query()->create([
        'name' => 'Mañana',
        'starts_at' => '06:30',
        'ends_at' => '12:00',
        'is_active' => true,
    ]);
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
    $subject = Subject::query()->create(['name' => 'Matemáticas', 'code' => 'MAT', 'is_active' => true]);
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();
    TeacherAssignment::query()->create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id]);
    $task = AcademicTask::factory()->create([
        'classroom_id' => $classroom->id,
        'academic_period_id' => $period->id,
        'subject_id' => $subject->id,
        'assigned_by_id' => $teacher->id,
    ]);
    TaskGrade::query()->create(['academic_task_id' => $task->id, 'enrollment_id' => $enrollment->id, 'graded_by_id' => $teacher->id, 'score' => 4.5]);

    return [$admin, $studentUser, $period];
}
