<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;

test('administrators can create teachers and assign them to an isolated course subject', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $this->actingAs($administrator)->post(route('admin.teachers.store'), ['name' => 'Carlos Ruiz', 'email' => 'carlos@example.com'])->assertSessionHasNoErrors();
    $teacher = User::query()->where('personal_email', 'carlos@example.com')->firstOrFail();
    $campus = Campus::query()->create(['name' => 'Principal', 'code' => 'PRI', 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => 'Mañana', 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
    $grade = Grade::factory()->create();
    $year = AcademicYear::factory()->create();
    $classroom = Classroom::query()->create(['name' => 'A', 'campus_id' => $campus->id, 'school_shift_id' => $shift->id, 'grade_id' => $grade->id, 'academic_year_id' => $year->id, 'capacity' => 35, 'is_active' => true]);
    $subject = Subject::query()->create(['name' => 'Matemáticas', 'code' => 'MAT', 'is_active' => true]);

    $this->actingAs($administrator)->post(route('admin.teachers.assign'), ['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id])->assertSessionHasNoErrors();

    expect($teacher->role)->toBe(UserRole::Teacher);
    $this->assertDatabaseHas('teacher_assignments', ['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id]);
});

test('a teacher assignment cannot use a non teacher account', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();

    $this->actingAs($administrator)->post(route('admin.teachers.assign'), ['teacher_id' => $administrator->id, 'classroom_id' => 999, 'subject_id' => 999])
        ->assertSessionHasErrors(['teacher_id', 'classroom_id', 'subject_id']);
    expect(TeacherAssignment::query()->count())->toBe(0);
});
