<?php

namespace App\Actions;

use App\Models\AcademicPeriod;
use App\Models\AcademicTask;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\GradingScale;
use App\Models\TaskGrade;
use Illuminate\Support\Facades\DB;

class SyncEvaluationGrades
{
    public function handle(Evaluation $evaluation, ?int $periodId = null): void
    {
        DB::transaction(function () use ($evaluation, $periodId): void {
            $evaluation = Evaluation::query()->lockForUpdate()->findOrFail($evaluation->id);
            $assignment = $evaluation->teacherAssignment;
            if ($evaluation->academic_task_id === null) {
                $periods = AcademicPeriod::query()->where('academic_year_id', $assignment->classroom->academic_year_id)->orderBy('number')->get();
                $matchingPeriod = $periods->first(fn (AcademicPeriod $period): bool => $evaluation->due_at->betweenIncluded($period->starts_at->startOfDay(), $period->ends_at->endOfDay()));
                $fallbackPeriod = $periods->first();
                if ($periodId === null) {
                    $periodId = $matchingPeriod instanceof AcademicPeriod
                        ? $matchingPeriod->id
                        : ($fallbackPeriod instanceof AcademicPeriod ? $fallbackPeriod->id : null);
                }
                $task = AcademicTask::query()->create([
                    'classroom_id' => $assignment->classroom_id,
                    'subject_id' => $assignment->subject_id,
                    'assigned_by_id' => $assignment->teacher_id,
                    'academic_period_id' => $periodId,
                    'title' => $evaluation->title,
                    'description' => $evaluation->description,
                    'type' => 'evaluacion',
                    'due_at' => $evaluation->due_at,
                    'published_at' => $evaluation->created_at,
                ]);
                $evaluation->academicTask()->associate($task);
                $evaluation->save();
            }

            $scale = GradingScale::current();
            $enrollments = Enrollment::query()->where('classroom_id', $assignment->classroom_id)
                ->with('student')->get()->keyBy('student.user_id');
            foreach ($evaluation->attempts()->whereNotNull('graded_at')->get() as $attempt) {
                $enrollment = $enrollments->get($attempt->user_id);
                if ($enrollment === null) {
                    continue;
                }
                TaskGrade::query()->updateOrCreate(
                    ['academic_task_id' => $evaluation->academic_task_id, 'enrollment_id' => $enrollment->id],
                    ['graded_by_id' => $assignment->teacher_id, 'score' => $scale->fromPercentage($attempt->score), 'observations' => $attempt->feedback],
                );
            }
        });
    }
}
