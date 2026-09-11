<?php

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('administrators can view and search enrollments', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $student = Student::factory()->create(['document_number' => 'DOC-123']);
    Enrollment::factory()->for($student)->create(['status' => EnrollmentStatus::Active]);

    $response = $this->actingAs($administrator)->get(route('admin.enrollments', ['search' => 'DOC-123']));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/enrollments/index')
        ->has('enrollments.data', 1)
        ->where('enrollments.data.0.document', 'DOC-123')
        ->where('filters.search', 'DOC-123')
    );
});

test('administrators can view active students with their current grade and history count', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $student = Student::factory()->create(['status' => StudentStatus::Active]);
    Enrollment::factory()->for($student)->create();

    $response = $this->actingAs($administrator)->get(route('admin.students'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/students/index')
        ->where('mode', 'active')
        ->has('students.data', 1)
        ->where('students.data.0.enrollments_count', 1)
    );
});

test('administrators can view graduates separately from active students', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    Student::factory()->create(['status' => StudentStatus::Active]);
    Student::factory()->create(['status' => StudentStatus::Graduated, 'graduated_at' => '2025-11-28']);

    $response = $this->actingAs($administrator)->get(route('admin.graduates'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/students/index')
        ->where('mode', 'graduates')
        ->has('students.data', 1)
        ->where('students.data.0.graduated_at', '2025-11-28')
    );
});

test('non administrators cannot view school records', function (string $routeName) {
    $user = User::factory()->withRole(UserRole::Teacher)->create();

    $response = $this->actingAs($user)->get(route($routeName));

    $response->assertForbidden();
})->with(['matrículas' => 'admin.enrollments', 'estudiantes' => 'admin.students', 'egresados' => 'admin.graduates']);
