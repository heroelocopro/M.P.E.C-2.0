<?php

use App\Enums\UserRole;
use App\Models\AcademicTaskAttachment;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('teachers see their assigned courses and subjects on the dashboard', function () {
    [$teacher, $classroom, $subject] = createTeacherWorkspace();
    $secondSubject = Subject::query()->create(['name' => 'Lenguaje', 'code' => 'LEN', 'is_active' => true]);
    TeacherAssignment::query()->create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $secondSubject->id]);

    $response = $this->actingAs($teacher)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('teacherDashboard.course_count', 1)
        ->where('teacherDashboard.subject_count', 2)
        ->where('teacherDashboard.student_count', 1)
        ->has('teacherDashboard.assignments', 2)
        ->where('teacherDashboard.assignments.0.subject', $subject->name));
});

test('teachers publish tasks only for their assignments and notify enrolled students', function () {
    [$teacher, $classroom, $subject, $studentUser] = createTeacherWorkspace();
    Storage::fake('local');
    $attachment = UploadedFile::fake()->create('guia-fracciones.pdf', 250, 'application/pdf');

    $response = $this->actingAs($teacher)->post(route('teacher.tasks.store'), [
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'title' => 'Taller de fracciones',
        'description' => 'Resolver los ejercicios asignados.',
        'type' => 'tarea',
        'due_at' => now()->addWeek()->toDateTimeString(),
        'attachments' => [$attachment],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('academic_tasks', ['assigned_by_id' => $teacher->id, 'title' => 'Taller de fracciones']);
    $storedAttachment = AcademicTaskAttachment::query()->firstOrFail();
    Storage::disk('local')->assertExists($storedAttachment->path);
    expect($studentUser->notifications()->where('data->title', 'Nueva tarea: Taller de fracciones')->exists())->toBeTrue();
    $this->actingAs($studentUser)->get(route('task-attachments.download', $storedAttachment))->assertDownload('guia-fracciones.pdf');
});

test('teachers cannot publish tasks for another teachers assignment', function () {
    [$teacher, $classroom, $subject] = createTeacherWorkspace();
    $otherTeacher = User::factory()->withRole(UserRole::Teacher)->create();

    $this->actingAs($otherTeacher)->post(route('teacher.tasks.store'), [
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'title' => 'Actividad ajena',
        'type' => 'tarea',
        'due_at' => now()->addWeek()->toDateTimeString(),
    ])->assertForbidden();

    $this->assertDatabaseMissing('academic_tasks', ['title' => 'Actividad ajena']);
});

test('teachers record attendance for students in an assigned classroom', function () {
    [$teacher, $classroom, , , $enrollment] = createTeacherWorkspace();

    $response = $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
        'classroom_id' => $classroom->id,
        'date' => now()->toDateString(),
        'attendance' => [$enrollment->id => 'tarde'],
    ]);

    $response->assertRedirect();
    expect(AttendanceRecord::query()->whereBelongsTo($enrollment)->value('status'))->toBe('tarde');
});

test('teachers save an excuse with an attendance record', function () {
    [$teacher, $classroom, , , $enrollment] = createTeacherWorkspace();

    $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
        'classroom_id' => $classroom->id,
        'date' => now()->toDateString(),
        'attendance' => [$enrollment->id => 'excusa'],
        'notes' => [$enrollment->id => 'Cita médica con soporte pendiente.'],
    ])->assertRedirect();

    $this->assertDatabaseHas('attendance_records', [
        'enrollment_id' => $enrollment->id,
        'status' => 'excusa',
        'notes' => 'Cita médica con soporte pendiente.',
    ]);
});

test('teachers can update attendance already saved for the same student and date', function () {
    [$teacher, $classroom, , , $enrollment] = createTeacherWorkspace();
    $date = now()->toDateString();
    AttendanceRecord::factory()->for($enrollment)->create([
        'date' => $date,
        'status' => 'ausente',
        'recorded_by_id' => $teacher->id,
    ]);

    $response = $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
        'classroom_id' => $classroom->id,
        'date' => $date,
        'attendance' => [$enrollment->id => 'presente'],
    ]);

    $response->assertRedirect();
    expect(AttendanceRecord::query()->whereBelongsTo($enrollment)->get())
        ->toHaveCount(1)
        ->first()->status->toBe('presente');
});

test('roles other than teacher cannot access the teacher workspace', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)->get(route('teacher.attendance'))->assertForbidden();
})->with(['administrator' => UserRole::Administrator, 'student' => UserRole::Student, 'guardian' => UserRole::Guardian]);

/** @return array{User, Classroom, Subject, User, Enrollment} */
function createTeacherWorkspace(): array
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
