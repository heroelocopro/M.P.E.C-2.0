<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTaskRequest;
use App\Http\Requests\Teacher\UpdateTaskDeadlineRequest;
use App\Models\AcademicPeriod;
use App\Models\AcademicTask;
use App\Models\AcademicTaskAttachment;
use App\Models\AcademicTaskSubmission;
use App\Models\Classroom;
use App\Models\TeacherAssignment;
use App\Notifications\SchoolAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function update(UpdateTaskDeadlineRequest $request, AcademicTask $academicTask): RedirectResponse
    {
        $academicTask->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fecha de entrega actualizada.']);

        return back();
    }

    public function index(Request $request): Response
    {
        $assignments = TeacherAssignment::query()
            ->whereBelongsTo($request->user(), 'teacher')
            ->with(['classroom.grade', 'classroom.campus', 'subject'])
            ->get();

        return Inertia::render('teacher/tasks', [
            'assignments' => $assignments->map(fn (TeacherAssignment $assignment): array => [
                'id' => $assignment->id,
                'classroom_id' => $assignment->classroom_id,
                'subject_id' => $assignment->subject_id,
                'label' => "{$assignment->subject->name} · {$assignment->classroom->grade->name} {$assignment->classroom->name} · {$assignment->classroom->campus->name}",
            ]),
            'periods' => AcademicPeriod::query()
                ->whereIn('academic_year_id', $assignments->pluck('classroom.academic_year_id')->unique())
                ->orderBy('academic_year_id')->orderBy('number')
                ->get()
                ->map(fn (AcademicPeriod $period): array => [
                    'id' => $period->id,
                    'academic_year_id' => $period->academic_year_id,
                    'name' => $period->name,
                ]),
            'tasks' => AcademicTask::query()
                ->whereIn('type', ['tarea', 'proyecto'])
                ->whereBelongsTo($request->user(), 'assignedBy')
                ->with(['classroom.grade', 'subject', 'academicPeriod', 'attachments', 'submissions.enrollment.student'])
                ->latest('published_at')
                ->get()
                ->map(fn (AcademicTask $task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'type' => $task->type,
                    'subject' => $task->subject->name,
                    'course' => "{$task->classroom->grade->name} {$task->classroom->name}",
                    'period' => $task->academicPeriod?->name,
                    'submissions' => $task->submissions->map(fn (AcademicTaskSubmission $submission): array => [
                        'id' => $submission->id,
                        'student' => collect([$submission->enrollment->student->first_name, $submission->enrollment->student->last_name])->filter()->join(' '),
                        'name' => $submission->original_name,
                        'url' => route('task-submissions.download', $submission, absolute: false),
                    ]),
                    'due_at' => $task->due_at->toIso8601String(),
                    'attachments' => $task->attachments->map(fn (AcademicTaskAttachment $attachment): array => [
                        'id' => $attachment->id,
                        'name' => $attachment->original_name,
                        'size' => $attachment->size,
                        'url' => route('task-attachments.download', $attachment, absolute: false),
                    ]),
                ]),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('attachments');
        $data['academic_period_id'] ??= AcademicPeriod::query()
            ->where('academic_year_id', Classroom::query()->whereKey($request->integer('classroom_id'))->value('academic_year_id'))
            ->orderBy('number')
            ->value('id');
        $disk = (string) config('filesystems.school_files');
        $storedPaths = [];

        try {
            $task = DB::transaction(function () use ($request, $data, $disk, &$storedPaths): AcademicTask {
                $task = AcademicTask::query()->create([
                    ...$data,
                    'assigned_by_id' => $request->user()->id,
                    'published_at' => now(),
                ]);

                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store("academic-tasks/{$task->id}", $disk);
                    abort_if($path === false, 500, 'No fue posible almacenar el archivo.');
                    $storedPaths[] = $path;
                    $task->attachments()->create([
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                        'size' => $file->getSize(),
                    ]);
                }

                return $task;
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($storedPaths);

            throw $exception;
        }

        $recipients = $task->classroom->enrollments()
            ->where('status', 'activa')
            ->with('student.user')
            ->get()
            ->pluck('student.user')
            ->filter();

        Notification::send($recipients, new SchoolAnnouncement(
            "Nueva {$task->type}: {$task->title}",
            "{$task->subject->name} · Entrega {$task->due_at->translatedFormat('j \d\e F')}",
            'tarea',
            route('student.tasks', absolute: false),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Actividad publicada y estudiantes notificados.']);

        return back();
    }
}
