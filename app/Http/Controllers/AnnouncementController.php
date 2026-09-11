<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\SchoolAnnouncement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Announcement::query()->with(['author:id,name', 'classrooms.grade']);

        if ($user->role === UserRole::Teacher) {
            $query->where(fn (Builder $query) => $query
                ->where('kind', 'institucional')
                ->orWhereBelongsTo($user, 'author'));
        } elseif ($user->role === UserRole::Student) {
            $classroomIds = $user->student?->enrollments()->where('status', EnrollmentStatus::Active)->pluck('classroom_id')->filter() ?? collect();
            $query->where(fn (Builder $query) => $query
                ->where('kind', 'institucional')
                ->orWhereHas('classrooms', fn (Builder $query) => $query->whereKey($classroomIds)));
        } elseif ($user->role === UserRole::Guardian) {
            $classroomIds = $user->guardianStudents()->with('enrollments')->get()
                ->flatMap(fn ($student) => $student->enrollments->where('status', EnrollmentStatus::Active)->pluck('classroom_id'))
                ->filter()->unique();
            $query->where(fn (Builder $query) => $query
                ->where('kind', 'institucional')
                ->orWhereHas('classrooms', fn (Builder $query) => $query->whereKey($classroomIds)));
        } else {
            $query->where('kind', 'institucional');
        }

        $classrooms = $user->role === UserRole::Teacher
            ? TeacherAssignment::query()
                ->whereBelongsTo($user, 'teacher')
                ->with(['classroom.grade', 'classroom.campus'])
                ->get()
                ->unique('classroom_id')
                ->map(fn (TeacherAssignment $assignment): array => [
                    'id' => $assignment->classroom->id,
                    'label' => "{$assignment->classroom->grade->name} {$assignment->classroom->name} · {$assignment->classroom->campus->name}",
                ])->values()
            : collect();

        return Inertia::render('announcements/index', [
            'can_publish' => in_array($user->role, [UserRole::Administrator, UserRole::Teacher], true),
            'publisher_role' => $user->role->value,
            'classrooms' => $classrooms,
            'announcements' => $query->latest('published_at')->get()->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'kind' => $announcement->kind,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'author' => $announcement->author->name,
                'published_at' => $announcement->published_at->toIso8601String(),
                'classrooms' => $announcement->classrooms->map(fn (Classroom $classroom): string => "{$classroom->grade->name} {$classroom->name}"),
            ]),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $user = $request->user();
        $audience = $request->string('audience')->toString();
        $classroomIds = match ($audience) {
            'all_classes' => $user->teacherAssignments()->pluck('classroom_id')->unique()->all(),
            'classroom' => [$request->integer('classroom_id')],
            default => [],
        };

        $announcement = DB::transaction(function () use ($request, $user, $classroomIds): Announcement {
            $announcement = Announcement::query()->create([
                'author_id' => $user->id,
                'kind' => $user->role === UserRole::Administrator ? 'institucional' : 'docente',
                'title' => $request->string('title')->toString(),
                'body' => $request->string('body')->toString(),
                'published_at' => now(),
            ]);
            $announcement->classrooms()->sync($classroomIds);

            return $announcement;
        });

        $recipients = $announcement->kind === 'institucional'
            ? User::query()->get()
            : User::query()
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query->where('role', UserRole::Student)
                        ->whereHas('student.enrollments', fn (Builder $query) => $query->whereIn('classroom_id', $classroomIds)->where('status', EnrollmentStatus::Active)))
                    ->orWhere(fn (Builder $query) => $query->where('role', UserRole::Guardian)
                        ->whereHas('guardianStudents.enrollments', fn (Builder $query) => $query->whereIn('classroom_id', $classroomIds)->where('status', EnrollmentStatus::Active))))
                ->get();

        Notification::send($recipients, new SchoolAnnouncement(
            $announcement->title,
            $announcement->body,
            $announcement->kind,
            route('announcements', absolute: false),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aviso publicado y destinatarios notificados.']);

        return back();
    }
}
