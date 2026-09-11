<?php

use App\Actions\CompleteEnrollment;
use App\Actions\EnrollStudent;
use App\Enums\DocumentType;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;

test('a first enrollment creates the student account and permanent student record', function () {
    $academicYear = AcademicYear::factory()->create(['name' => '2026']);
    $grade = Grade::factory()->create(['name' => 'Sexto', 'code' => '06']);

    $enrollment = app(EnrollStudent::class)->handle([
        'enrollment_number' => 'MAT-2026-0001',
        'document_type' => DocumentType::IdentityCard,
        'document_number' => '1002003001',
        'first_name' => 'Laura',
        'middle_name' => 'Sofía',
        'last_name' => 'Martínez',
        'second_last_name' => 'López',
        'birth_date' => '2013-05-20',
        'email' => 'laura@aula.test',
    ], $academicYear, $grade);

    $student = $enrollment->student;
    $user = $student->user;

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Laura Sofía Martínez López')
        ->and($user->role)->toBe(UserRole::Student)
        ->and($student->document_type)->toBe(DocumentType::IdentityCard)
        ->and($student->status)->toBe(StudentStatus::Active)
        ->and($enrollment->status)->toBe(EnrollmentStatus::Active)
        ->and($enrollment->grade->is($grade))->toBeTrue()
        ->and($enrollment->academicYear->is($academicYear))->toBeTrue();
});

test('a new school year reuses the student and preserves previous enrollments', function () {
    $student = Student::factory()->create([
        'document_type' => DocumentType::IdentityCard,
        'document_number' => '1002003002',
    ]);
    $previousYear = AcademicYear::factory()->create(['name' => '2025']);
    $currentYear = AcademicYear::factory()->create(['name' => '2026']);
    $previousGrade = Grade::factory()->create(['name' => 'Quinto', 'code' => '05']);
    $currentGrade = Grade::factory()->create(['name' => 'Sexto', 'code' => '06']);
    Enrollment::factory()->for($student)->for($previousYear)->for($previousGrade)->create([
        'status' => EnrollmentStatus::Completed,
    ]);

    $currentEnrollment = app(EnrollStudent::class)->handle([
        'enrollment_number' => 'MAT-2026-0002',
        'document_type' => DocumentType::IdentityCard,
        'document_number' => '1002003002',
        'first_name' => $student->first_name,
        'last_name' => $student->last_name,
        'birth_date' => $student->birth_date->toDateString(),
        'email' => $student->user->email,
    ], $currentYear, $currentGrade);

    expect(Student::query()->count())->toBe(1)
        ->and(User::query()->count())->toBe(1)
        ->and($currentEnrollment->student->is($student))->toBeTrue()
        ->and($student->enrollments()->count())->toBe(2)
        ->and($student->enrollments()->whereBelongsTo($previousYear)->sole()->grade->is($previousGrade))->toBeTrue();
});

test('a student cannot have two enrollments in the same school year', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create(['name' => '2026']);
    $grade = Grade::factory()->create();
    Enrollment::factory()->for($student)->for($academicYear)->for($grade)->create();

    $action = fn () => app(EnrollStudent::class)->handle([
        'enrollment_number' => 'MAT-2026-DUPLICADA',
        'document_type' => $student->document_type,
        'document_number' => $student->document_number,
        'first_name' => $student->first_name,
        'last_name' => $student->last_name,
        'birth_date' => $student->birth_date->toDateString(),
        'email' => $student->user->email,
    ], $academicYear, $grade);

    expect($action)->toThrow(DomainException::class, 'ya tiene una matrícula')
        ->and(Enrollment::query()->count())->toBe(1);
});

test('completing the final grade marks the student as graduated and keeps their history', function () {
    $student = Student::factory()->create();
    $previousEnrollment = Enrollment::factory()->for($student)->create([
        'status' => EnrollmentStatus::Completed,
    ]);
    $finalGrade = Grade::factory()->create(['name' => 'Undécimo', 'code' => '11', 'is_final' => true]);
    $finalEnrollment = Enrollment::factory()->for($student)->for($finalGrade)->create();

    $completedEnrollment = app(CompleteEnrollment::class)->handle($finalEnrollment);

    expect($completedEnrollment->status)->toBe(EnrollmentStatus::Completed)
        ->and($completedEnrollment->completed_at)->not->toBeNull()
        ->and($student->refresh()->status)->toBe(StudentStatus::Graduated)
        ->and($student->graduated_at)->not->toBeNull()
        ->and($student->enrollments()->count())->toBe(2)
        ->and($student->enrollments()->find($previousEnrollment->id))->not->toBeNull();
});

test('graduated students cannot receive another enrollment', function () {
    $student = Student::factory()->create([
        'status' => StudentStatus::Graduated,
        'graduated_at' => today(),
    ]);
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();

    $action = fn () => app(EnrollStudent::class)->handle([
        'enrollment_number' => 'MAT-EGRESADO',
        'document_type' => $student->document_type,
        'document_number' => $student->document_number,
        'first_name' => $student->first_name,
        'last_name' => $student->last_name,
        'birth_date' => $student->birth_date->toDateString(),
        'email' => $student->user->email,
    ], $academicYear, $grade);

    expect($action)->toThrow(DomainException::class, 'egresado')
        ->and(Enrollment::query()->count())->toBe(0);
});
