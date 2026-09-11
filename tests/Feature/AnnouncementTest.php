<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('an institutional announcement is visible and notified to every role', function () {
    $admin = User::factory()->withRole(UserRole::Administrator)->create();
    $users = collect(UserRole::cases())->map(fn (UserRole $role) => User::factory()->withRole($role)->create());

    $this->actingAs($admin)->post(route('announcements.store'), [
        'title' => 'Suspensión de actividades',
        'body' => 'La institución no tendrá clases el viernes.',
        'audience' => 'institutional',
    ])->assertRedirect();

    foreach ($users as $user) {
        expect($user->notifications()->where('data->title', 'Suspensión de actividades')->exists())->toBeTrue();
        $this->actingAs($user)->get(route('announcements'))->assertInertia(fn (Assert $page) => $page
            ->where('announcements.0.kind', 'institucional')
            ->where('announcements.0.title', 'Suspensión de actividades'));
    }
});

test('a teacher announcement reaches only students in the selected classroom', function () {
    [$teacher, $firstClassroom, $firstStudent] = createAnnouncementAudience('A');
    [, $secondClassroom, $secondStudent] = createAnnouncementAudience('B', $teacher);

    $this->actingAs($teacher)->post(route('announcements.store'), [
        'title' => 'Material de matemáticas',
        'body' => 'Traer regla y colores.',
        'audience' => 'classroom',
        'classroom_id' => $firstClassroom->id,
    ])->assertRedirect();

    expect($firstStudent->notifications()->where('data->title', 'Material de matemáticas')->exists())->toBeTrue();
    expect($secondStudent->notifications()->where('data->title', 'Material de matemáticas')->exists())->toBeFalse();
    $this->actingAs($firstStudent)->get(route('announcements'))->assertInertia(fn (Assert $page) => $page->has('announcements', 1));
    $this->actingAs($secondStudent)->get(route('announcements'))->assertInertia(fn (Assert $page) => $page->has('announcements', 0));
    expect(Announcement::query()->firstOrFail()->classrooms()->pluck('classrooms.id')->all())->toBe([$firstClassroom->id]);
    expect($secondClassroom->id)->not->toBe($firstClassroom->id);
});

test('teachers can send an announcement to all of their assigned classrooms', function () {
    [$teacher, , $firstStudent] = createAnnouncementAudience('A');
    [, , $secondStudent] = createAnnouncementAudience('B', $teacher);

    $this->actingAs($teacher)->post(route('announcements.store'), [
        'title' => 'Reunión de cursos',
        'body' => 'Revisaremos el proyecto el lunes.',
        'audience' => 'all_classes',
    ])->assertRedirect();

    expect($firstStudent->notifications()->where('data->title', 'Reunión de cursos')->exists())->toBeTrue();
    expect($secondStudent->notifications()->where('data->title', 'Reunión de cursos')->exists())->toBeTrue();
});

test('a teacher cannot target a classroom that is not assigned to them', function () {
    [$teacher] = createAnnouncementAudience('A');
    [, $otherClassroom] = createAnnouncementAudience('B');

    $this->actingAs($teacher)->post(route('announcements.store'), [
        'title' => 'Aviso indebido',
        'body' => 'Este mensaje no debe publicarse.',
        'audience' => 'classroom',
        'classroom_id' => $otherClassroom->id,
    ])->assertForbidden();

    $this->assertDatabaseMissing('announcements', ['title' => 'Aviso indebido']);
});

/** @return array{User, Classroom, User} */
function createAnnouncementAudience(string $classroomName, ?User $teacher = null): array
{
    $teacher ??= User::factory()->withRole(UserRole::Teacher)->create();
    $studentUser = User::factory()->withRole(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();
    $year = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $campus = Campus::query()->create(['name' => "Sede {$classroomName}", 'code' => "SEDE-{$classroomName}", 'is_active' => true]);
    $shift = SchoolShift::query()->create(['name' => "Jornada {$classroomName}", 'starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
    $classroom = Classroom::query()->create(['campus_id' => $campus->id, 'school_shift_id' => $shift->id, 'grade_id' => $grade->id, 'academic_year_id' => $year->id, 'name' => $classroomName, 'capacity' => 35, 'is_active' => true]);
    Enrollment::factory()->for($student)->for($year)->for($grade)->create(['classroom_id' => $classroom->id]);
    $subject = Subject::query()->create(['name' => "Materia {$classroomName}", 'code' => "MAT-{$classroomName}", 'is_active' => true]);
    TeacherAssignment::query()->create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id]);

    return [$teacher, $classroom, $studentUser];
}
