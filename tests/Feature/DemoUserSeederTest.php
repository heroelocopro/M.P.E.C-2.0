<?php

use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Evaluation;
use App\Models\ForumTopic;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\TaskGrade;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\SchoolStructureSeeder;

test('creates one reusable demo account for every school role', function () {
    $this->seed(SchoolStructureSeeder::class);
    $this->seed(DemoUserSeeder::class);
    $this->seed(DemoUserSeeder::class);

    expect(User::query()->count())->toBe(4);

    foreach (UserRole::cases() as $role) {
        $user = User::query()->where('role', $role)->sole();

        expect($user->role)->toBe($role)
            ->and($user->email_verified_at)->not->toBeNull();
    }

    $student = Student::query()->with('enrollments.academicYear')->sole();

    expect($student->enrollments)->toHaveCount(2)
        ->and($student->enrollments->pluck('academicYear.name')->sort()->values()->all())
        ->toBe(['2025', '2026'])
        ->and(TaskGrade::query()->count())->toBeGreaterThanOrEqual(5)
        ->and(AttendanceRecord::query()->count())->toBe(5)
        ->and(Evaluation::query()->count())->toBe(1)
        ->and(ForumTopic::query()->count())->toBe(1)
        ->and(StudentReport::query()->count())->toBe(1);
});
