<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicTask;
use App\Models\AcademicTaskAttachment;
use App\Models\AcademicTaskSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $status = $request->query('status', 'available');
        if (! in_array($status, ['available', 'expired', 'all'], true)) {
            $status = 'available';
        }
        $student = $request->user()->student;
        $enrollment = $student?->enrollments()
            ->whereNotNull('classroom_id')
            ->where('status', 'activa')
            ->latest('enrolled_at')
            ->first();

        $taskQuery = AcademicTask::query()
            ->whereIn('type', ['tarea', 'proyecto'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($status === 'available', fn ($query) => $query->where('due_at', '>=', now()))
            ->when($status === 'expired', fn ($query) => $query->where('due_at', '<', now()))
            ->with(['subject:id,name', 'assignedBy:id,name', 'academicPeriod', 'attachments', 'submissions' => fn ($query) => $query->where('enrollment_id', $enrollment?->id)])
            ->orderBy('due_at');

        $enrollment
            ? $taskQuery->whereBelongsTo($enrollment->classroom)
            : $taskQuery->whereKey([]);

        $tasks = $taskQuery->get()
            ->map(fn (AcademicTask $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'type' => $task->type,
                'subject' => $task->subject->name,
                'teacher' => $task->assignedBy->name,
                'period' => $task->academicPeriod?->name,
                'due_at' => $task->due_at->toIso8601String(),
                'submission' => $task->submissions->first() instanceof AcademicTaskSubmission
                    ? [
                        'id' => $task->submissions->first()->id,
                        'name' => $task->submissions->first()->original_name,
                        'submitted_at' => $task->submissions->first()->submitted_at->toIso8601String(),
                        'url' => route('task-submissions.download', $task->submissions->first(), absolute: false),
                    ]
                    : null,
                'is_overdue' => $task->due_at->isPast(),
                'attachments' => $task->attachments->map(fn (AcademicTaskAttachment $attachment): array => [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name,
                    'size' => $attachment->size,
                    'url' => route('task-attachments.download', $attachment, absolute: false),
                ]),
            ]);

        return Inertia::render('student/tasks', ['tasks' => $tasks, 'status' => $status]);
    }
}
