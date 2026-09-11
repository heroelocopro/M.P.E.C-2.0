<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\SchoolStructureSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('administrators can visit their dashboard', function () {
    $user = User::factory()->withRole(UserRole::Administrator)->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
    );
});

test('administrator dashboard displays real academic indicators', function () {
    $this->seed([SchoolStructureSeeder::class, DemoUserSeeder::class]);
    $administrator = User::query()->where('email', 'admin@aula.test')->sole();

    $this->actingAs($administrator)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('adminDashboard.student_count', 1)
        ->where('adminDashboard.teacher_count', 1)
        ->where('adminDashboard.academic_year', '2026')
        ->where('adminDashboard.period', 'Periodo 4')
        ->where('adminDashboard.average', 4.55)
        ->where('adminDashboard.attendance_percentage', 60));
});

test('students see the details of their active enrollment', function () {
    $user = User::factory()->withRole(UserRole::Student)->create(['name' => 'Laura Martínez']);
    $student = Student::factory()->for($user)->create(['first_name' => 'Laura']);
    $academicYear = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true]);
    $grade = Grade::factory()->create(['name' => 'Sexto']);
    $campus = Campus::query()->create(['name' => 'Sede Principal', 'code' => 'PRI', 'address' => 'Calle 10', 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => 'Mañana', 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
    $classroom = Classroom::query()->create([
        'campus_id' => $campus->id,
        'school_shift_id' => $shift->id,
        'grade_id' => $grade->id,
        'academic_year_id' => $academicYear->id,
        'name' => 'A',
        'capacity' => 35,
        'is_active' => true,
    ]);
    Enrollment::factory()->for($student)->for($academicYear)->for($grade)->create([
        'classroom_id' => $classroom->id,
        'enrollment_number' => 'MAT-2026-001',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('studentDashboard.student_name', 'Laura')
        ->where('studentDashboard.academic_year', '2026')
        ->where('studentDashboard.grade', 'Sexto')
        ->where('studentDashboard.classroom', 'A')
        ->where('studentDashboard.campus', 'Sede Principal')
        ->where('studentDashboard.shift', 'Mañana')
        ->where('studentDashboard.enrollment_number', 'MAT-2026-001')
        ->where('studentDashboard.average', null)
        ->where('studentDashboard.pending_assignments', 0)
        ->where('studentDashboard.recent_activity', [])
    );
});

test('students without an enrollment receive an empty dashboard state', function () {
    $user = User::factory()->withRole(UserRole::Student)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('studentDashboard.grade', null)
        ->where('studentDashboard.classroom', null)
        ->where('studentDashboard.recent_activity', [])
    );
});
