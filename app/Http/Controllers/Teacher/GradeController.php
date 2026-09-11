<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTaskGradesRequest;
use App\Models\AcademicPeriod;
use App\Models\AcademicTask;
use App\Models\Enrollment;
use App\Models\TaskGrade;
use App\Models\TeacherAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function index(Request $request): Response
    {
        $assignments = TeacherAssignment::query()
            ->whereBelongsTo($request->user(), 'teacher')
            ->with(['subject:id,name', 'classroom.grade:id,name', 'classroom.campus:id,name'])
            ->get();

        $selectedAssignmentId = $request->integer('assignment') ?: $assignments->first()?->id;
        abort_if($selectedAssignmentId && ! $assignments->contains('id', $selectedAssignmentId), 403);
        $selectedAssignment = $selectedAssignmentId
            ? $assignments->firstWhere('id', $selectedAssignmentId)
            : null;
        $periods = $selectedAssignment
            ? AcademicPeriod::query()
                ->where('academic_year_id', $selectedAssignment->classroom->academic_year_id)
                ->orderBy('number')
                ->get()
            : collect();
        $selectedPeriodId = $request->integer('period') ?: $periods->first()?->id;
        abort_if($selectedPeriodId && ! $periods->contains('id', $selectedPeriodId), 403);

        $tasks = $selectedAssignment
            ? AcademicTask::query()
                ->whereBelongsTo($request->user(), 'assignedBy')
                ->where('classroom_id', $selectedAssignment->classroom_id)
                ->where('subject_id', $selectedAssignment->subject_id)
                ->where('academic_period_id', $selectedPeriodId)
                ->oldest('due_at')
                ->get()
            : collect();

        $students = $selectedAssignment
            ? Enrollment::query()
                ->where('classroom_id', $selectedAssignment->classroom_id)
                ->where('status', EnrollmentStatus::Active)
                ->with(['student', 'taskGrades' => fn ($query) => $query->whereIn('academic_task_id', $tasks->pluck('id'))])
                ->get()
                ->sortBy('student.last_name')
                ->map(fn (Enrollment $enrollment): array => [
                    'enrollment_id' => $enrollment->id,
                    'name' => collect([
                        $enrollment->student->first_name,
                        $enrollment->student->middle_name,
                        $enrollment->student->last_name,
                        $enrollment->student->second_last_name,
                    ])->filter()->join(' '),
                    'document_number' => $enrollment->student->document_number,
                    'grades' => $enrollment->taskGrades->mapWithKeys(fn (TaskGrade $grade): array => [
                        $grade->academic_task_id => $grade->score,
                    ]),
                    'final_grade' => $enrollment->taskGrades->isEmpty()
                        ? null
                        : round((float) $enrollment->taskGrades->avg('score'), 2),
                ])->values()
            : collect();

        return Inertia::render('teacher/grades', [
            'assignments' => $assignments->map(fn (TeacherAssignment $assignment): array => [
                'id' => $assignment->id,
                'label' => "{$assignment->subject->name} · {$assignment->classroom->grade->name} {$assignment->classroom->name} · {$assignment->classroom->campus->name}",
            ]),
            'selected_assignment_id' => $selectedAssignmentId,
            'periods' => $periods->map(fn (AcademicPeriod $period): array => [
                'id' => $period->id,
                'name' => $period->name,
                'is_closed' => $period->is_closed,
            ]),
            'selected_period_id' => $selectedPeriodId,
            'tasks' => $tasks->map(fn (AcademicTask $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'type' => $task->type,
                'due_at' => $task->due_at->toIso8601String(),
            ]),
            'students' => $students,
        ]);
    }

    public function store(StoreTaskGradesRequest $request): RedirectResponse
    {
        $grades = $request->safe()->array('grades');
        $assignment = TeacherAssignment::query()->findOrFail($request->integer('assignment_id'));
        $tasks = AcademicTask::query()
            ->whereBelongsTo($request->user(), 'assignedBy')
            ->where('classroom_id', $assignment->classroom_id)
            ->where('subject_id', $assignment->subject_id)
            ->when($request->filled('period_id'), fn ($query) => $query->where('academic_period_id', $request->integer('period_id')))
            ->whereKey(array_keys($grades))
            ->get();
        abort_if($tasks->count() !== count($grades), 422);

        $enrollmentIds = [];
        foreach ($grades as $taskGrades) {
            $enrollmentIds = [...$enrollmentIds, ...array_keys($taskGrades)];
        }
        $enrollmentIds = array_values(array_unique($enrollmentIds));
        $enrollments = Enrollment::query()
            ->where('classroom_id', $assignment->classroom_id)
            ->where('status', EnrollmentStatus::Active)
            ->whereKey($enrollmentIds)
            ->get();
        abort_if($enrollments->count() !== count($enrollmentIds), 422);

        DB::transaction(function () use ($grades, $request): void {
            foreach ($grades as $taskId => $taskGrades) {
                foreach ($taskGrades as $enrollmentId => $score) {
                    if ($score === null || $score === '') {
                        TaskGrade::query()
                            ->where('academic_task_id', $taskId)
                            ->where('enrollment_id', $enrollmentId)
                            ->delete();

                        continue;
                    }

                    TaskGrade::query()->updateOrCreate(
                        ['academic_task_id' => $taskId, 'enrollment_id' => $enrollmentId],
                        ['graded_by_id' => $request->user()->id, 'score' => $score],
                    );
                }
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Notas guardadas correctamente.']);

        return back();
    }
}
