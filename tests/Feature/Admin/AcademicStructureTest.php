<?php

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('administrators can create and update campuses', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();

    $this->actingAs($administrator)->post(route('admin.academic-settings.store', 'campuses'), [
        'name' => 'Sede Sur', 'code' => 'SUR', 'address' => 'Calle 1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $campus = Campus::query()->where('code', 'SUR')->firstOrFail();
    $this->actingAs($administrator)->put(route('admin.academic-settings.update', ['campuses', $campus]), [
        'name' => 'Sede Sur Renovada', 'code' => 'SUR',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('campuses', ['id' => $campus->id, 'name' => 'Sede Sur Renovada']);
});

test('administrators cannot delete a campus used by a classroom', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $campus = Campus::query()->create(['name' => 'Sede Protegida', 'code' => 'PRO', 'is_active' => true]);
    $year = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $shift = SchoolShift::query()->create(['name' => 'Única', 'starts_at' => '07:00', 'ends_at' => '13:00', 'is_active' => true]);
    Classroom::query()->create(['name' => 'A', 'campus_id' => $campus->id, 'school_shift_id' => $shift->id, 'grade_id' => $grade->id, 'academic_year_id' => $year->id, 'capacity' => 30, 'is_active' => true]);

    $this->actingAs($administrator)->delete(route('admin.academic-settings.destroy', ['campuses', $campus]))
        ->assertSessionHasErrors('structure');
    $this->assertDatabaseHas('campuses', ['id' => $campus->id]);
});

test('academic configuration only lists periods from the active year', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $activeYear = AcademicYear::factory()->create(['name' => '2026', 'is_active' => true]);
    $oldYear = AcademicYear::factory()->create(['name' => '2025', 'is_active' => false]);
    AcademicPeriod::factory()->create(['academic_year_id' => $activeYear->id, 'name' => 'Periodo actual']);
    AcademicPeriod::factory()->create(['academic_year_id' => $oldYear->id, 'name' => 'Periodo anterior']);

    $this->actingAs($administrator)->get(route('admin.academic-settings'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('periods', 1)->where('periods.0.name', 'Periodo actual'));
});

test('administrators can manually close an open period', function () {
    $administrator = User::factory()->withRole(UserRole::Administrator)->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $period = AcademicPeriod::factory()->for($year)->create(['is_closed' => false]);

    $this->actingAs($administrator)
        ->post(route('admin.periods.close', $period))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($period->refresh()->is_closed)->toBeTrue()
        ->and($year->refresh()->is_active)->toBeFalse();
});

test('non administrators cannot manually close periods', function () {
    $teacher = User::factory()->withRole(UserRole::Teacher)->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $period = AcademicPeriod::factory()->for($year)->create(['is_closed' => false]);

    $this->actingAs($teacher)->post(route('admin.periods.close', $period))->assertForbidden();

    expect($period->refresh()->is_closed)->toBeFalse();
});
