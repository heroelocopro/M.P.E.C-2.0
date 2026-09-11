<?php

use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('administrators can open the enrollment creation form', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    AcademicYear::factory()->create(['is_active' => true]);
    Grade::factory()->create();

    $this->actingAs($administrator)->get(route('admin.enrollments.create'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/enrollments/create')->has('classrooms')->has('documentTypes'));
});

test('administrators can create a student and enrollment', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $year = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $classroom = classroomFor($year, $grade);

    $response = $this->actingAs($administrator)->post(route('admin.enrollments.store'), enrollmentPayload($classroom));

    $response->assertRedirect(route('admin.enrollments'))->assertSessionHasNoErrors();
    expect(Student::query()->where('document_number', 'TI-9001')->exists())->toBeTrue()
        ->and(Enrollment::query()->where('enrollment_number', 'MAT-9001')->exists())->toBeTrue();
    $guardian = User::query()->where('personal_email', 'maria.garcia@example.com')->firstOrFail();
    expect($guardian->role)->toBe(UserRole::Guardian)
        ->and($guardian->guardianStudents()->where('document_number', 'TI-9001')->exists())->toBeTrue();
});

test('enrollment creation validates required student data', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();

    $this->actingAs($administrator)->post(route('admin.enrollments.store'), [])
        ->assertSessionHasErrors(['enrollment_number', 'classroom_id', 'document_number', 'email']);
});

test('administrators can import enrollments from a csv exported by excel', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $year = AcademicYear::factory()->create(['name' => '2027']);
    $grade = Grade::factory()->create(['name' => 'Sexto']);
    classroomFor($year, $grade);
    $csv = "numero_matricula,ano_lectivo,grado,sede,jornada,grupo,tipo_documento,numero_documento,primer_nombre,primer_apellido,fecha_nacimiento,correo,fecha_matricula\nMAT-CSV-1,2027,Sexto,Principal,Mañana,A,tarjeta_identidad,TI-CSV-1,Ana,Perez,2013-04-10,ana.csv@example.com,2027-01-20";

    $response = $this->actingAs($administrator)->post(route('admin.enrollments.import'), [
        'file' => UploadedFile::fake()->createWithContent('matriculas.csv', $csv),
    ]);

    $response->assertRedirect(route('admin.enrollments'))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('enrollments', ['enrollment_number' => 'MAT-CSV-1']);
});

test('administrators can view a students complete academic history', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $student = Student::factory()->create();
    Enrollment::factory()->count(2)->for($student)->create();

    $this->actingAs($administrator)->get(route('admin.students.show', $student))
        ->assertInertia(fn (Assert $page) => $page->component('admin/students/show')->where('student.id', $student->id)->has('student.enrollments', 2));
});

test('non administrators cannot manage enrollments or view academic history', function (string $routeName, string $method) {
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();
    $student = Student::factory()->create();
    $parameters = $routeName === 'admin.students.show' ? [$student] : [];

    $this->actingAs($teacher)->call($method, route($routeName, $parameters))->assertForbidden();
})->with([
    'creation form' => ['admin.enrollments.create', 'GET'],
    'store enrollment' => ['admin.enrollments.store', 'POST'],
    'import enrollment' => ['admin.enrollments.import', 'POST'],
    'academic history' => ['admin.students.show', 'GET'],
]);

/** @return array<string, mixed> */
function enrollmentPayload(Classroom $classroom): array
{
    return [
        'enrollment_number' => 'MAT-9001', 'classroom_id' => $classroom->id,
        'document_type' => DocumentType::IdentityCard->value, 'document_number' => 'TI-9001',
        'first_name' => 'Laura', 'last_name' => 'García', 'birth_date' => '2012-05-12',
        'email' => 'laura.9001@example.com', 'enrolled_at' => '2026-01-20',
        'guardian_name' => 'María García', 'guardian_email' => 'maria.garcia@example.com',
        'guardian_relationship' => 'Madre',
    ];
}

function classroomFor(AcademicYear $year, Grade $grade): Classroom
{
    $campus = Campus::query()->create(['name' => 'Principal', 'code' => 'PRI', 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => 'Mañana', 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);

    return Classroom::query()->create(['name' => 'A', 'campus_id' => $campus->id, 'school_shift_id' => $shift->id, 'grade_id' => $grade->id, 'academic_year_id' => $year->id, 'capacity' => 35, 'is_active' => true]);
}
