<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentReport;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $academicYear = AcademicYear::query()->where('is_active', true)->first();
        $periods = $academicYear
            ? AcademicPeriod::query()->whereBelongsTo($academicYear)->orderBy('number')->get()
            : collect();
        $currentPeriod = $periods->first(fn (AcademicPeriod $period): bool => now()->between($period->starts_at, $period->ends_at))
            ?? $periods->firstWhere('is_closed', false)
            ?? $periods->last();

        $students = $request->user()->guardianStudents()->with([
            'user:id,name,email',
            'enrollments' => fn ($query) => $query
                ->when($academicYear, fn ($enrollments) => $enrollments->where('academic_year_id', $academicYear->id))
                ->with(['grade', 'classroom', 'attendanceRecords', 'taskGrades.academicTask'])
                ->latest('enrolled_at'),
            'reports' => fn ($query) => $query->with('reportedBy:id,name')->latest('reported_at')->limit(10),
        ])->get();

        return Inertia::render('guardian/dashboard', ['students' => $students->map(function (Student $student) use ($periods, $currentPeriod): array {
            $enrollment = $student->enrollments->first();
            $records = $enrollment === null ? collect() : $enrollment->attendanceRecords;
            $pivot = $student->getRelation('pivot');
            $periodAverages = $enrollment
                ? $periods->mapWithKeys(fn (AcademicPeriod $period): array => [$period->id => $this->periodAverage($enrollment, $period)])
                : collect();
            $gradedPeriodAverages = $periodAverages->filter(fn (?float $average): bool => $average !== null);

            return [
                'id' => $student->id,
                'name' => $student->user?->name,
                'email' => $student->user?->email,
                'document' => $student->document_number,
                'relationship' => $pivot instanceof Pivot ? $pivot->getAttribute('relationship') : null,
                'course' => $enrollment ? $enrollment->grade->name.' '.$enrollment->classroom?->name : null,
                'grades' => [
                    'current_period' => $currentPeriod?->name,
                    'current_average' => $currentPeriod ? $periodAverages->get($currentPeriod->id) : null,
                    'year_average' => $gradedPeriodAverages->isEmpty() ? null : round((float) $gradedPeriodAverages->avg(), 2),
                ],
                'attendance' => [
                    'total' => $records->count(),
                    'present' => $records->where('status', 'presente')->count(),
                    'late' => $records->where('status', 'tarde')->count(),
                    'absent' => $records->whereIn('status', ['ausente', 'excusa'])->count(),
                ],
                'reports' => $student->reports->map(fn (StudentReport $report): array => [
                    'id' => $report->id, 'category' => $report->category, 'title' => $report->title,
                    'details' => $report->details, 'author' => $report->reportedBy->name,
                    'reported_at' => $report->reported_at->toIso8601String(),
                ]),
            ];
        })]);
    }

    private function periodAverage(Enrollment $enrollment, AcademicPeriod $period): ?float
    {
        $grades = $enrollment->taskGrades->filter(
            fn ($grade): bool => $grade->academicTask->academic_period_id === $period->id,
        );
        $subjectAverages = $grades
            ->groupBy(fn ($grade) => $grade->academicTask->subject_id)
            ->map(fn (Collection $subjectGrades): float => (float) $subjectGrades->avg('score'));

        return $subjectAverages->isEmpty() ? null : round((float) $subjectAverages->avg(), 2);
    }
}
