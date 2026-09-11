<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\GradingScale;
use App\Services\SimplePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class GradeController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $student = $request->user()->student;
        $enrollments = $student?->enrollments()->with(['academicYear', 'grade', 'classroom'])->latest('enrolled_at')->get() ?? collect();
        $periods = AcademicPeriod::query()
            ->with('academicYear')
            ->whereIn('academic_year_id', $enrollments->pluck('academic_year_id'))
            ->orderByDesc('academic_year_id')
            ->orderBy('number')
            ->get();
        $defaultPeriod = $periods->first(fn (AcademicPeriod $item): bool => now()->between($item->starts_at, $item->ends_at)) ?? $periods->first();
        $selectedPeriodId = $request->integer('period') ?: $defaultPeriod?->id;
        $period = $periods->firstWhere('id', $selectedPeriodId);
        $enrollment = $period ? $enrollments->firstWhere('academic_year_id', $period->academic_year_id) : null;

        return Inertia::render('student/grades', [
            'periods' => $periods->map(fn (AcademicPeriod $item): array => ['id' => $item->id, 'name' => $item->name, 'year' => $item->academicYear->name]),
            'selected_period_id' => $period?->id,
            'report' => $enrollment && $period ? $this->report($enrollment, $period) : null,
        ]);
    }

    public function download(Request $request, AcademicPeriod $period, SimplePdf $pdf): Response
    {
        $student = $request->user()->student;
        $enrollment = $student?->enrollments()->where('academic_year_id', $period->academic_year_id)->with(['academicYear', 'grade', 'classroom'])->first();
        abort_unless($enrollment !== null, 404);
        $report = $this->report($enrollment, $period);

        $institutionName = config('app.name') === 'Laravel' ? 'Aula' : (string) config('app.name');
        $filename = Str::slug("{$institutionName} {$period->name} {$enrollment->academicYear->name}").'.pdf';

        return response($pdf->make([
            'report' => $report,
            'period' => $period,
            'institution_name' => $institutionName,
            'gradingScale' => GradingScale::current()->limits(),
        ]), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** @return array<string, mixed> */
    private function report(Enrollment $enrollment, AcademicPeriod $period): array
    {
        $subjects = $period->is_closed
            ? $enrollment->periodFinalGrades()->whereBelongsTo($period, 'academicPeriod')->with('subject')->get()->map(fn ($grade): array => [
                'name' => $grade->subject->name,
                'score' => $grade->final_score,
                'activities' => $grade->activities_count,
            ])
            : $enrollment->taskGrades()->with('academicTask.subject')->whereHas('academicTask', fn ($query) => $query->where('academic_period_id', $period->id))->get()
                ->groupBy(fn ($grade) => $grade->academicTask->subject_id)->map(fn ($items): array => [
                    'name' => $items->first()->academicTask->subject->name,
                    'score' => round((float) $items->avg('score'), 2),
                    'activities' => $items->count(),
                ])->values();

        return [
            'student' => collect([$enrollment->student->first_name, $enrollment->student->middle_name, $enrollment->student->last_name, $enrollment->student->second_last_name])->filter()->join(' '),
            'year' => $enrollment->academicYear->name,
            'grade' => $enrollment->grade->name.' '.$enrollment->classroom?->name,
            'subjects' => $subjects,
            'average' => $subjects->isEmpty() ? 0 : round((float) $subjects->avg('score'), 2),
        ];
    }
}
