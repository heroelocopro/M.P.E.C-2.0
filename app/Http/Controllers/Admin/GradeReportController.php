<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeReportController extends Controller
{
    public function index(Request $request): Response
    {
        $years = AcademicYear::query()->orderByDesc('name')->get(['id', 'name', 'is_active']);
        $selectedYearId = $request->integer('year') ?: $years->firstWhere('is_active', true)?->id ?: $years->first()?->id;
        $periods = AcademicPeriod::query()
            ->with('academicYear')
            ->where('academic_year_id', $selectedYearId)
            ->orderBy('number')
            ->get();
        $period = $periods->firstWhere('id', $request->integer('period')) ?? $periods->first();
        $enrollments = $period ? Enrollment::query()->where('academic_year_id', $period->academic_year_id)->with(['student', 'grade', 'classroom', 'taskGrades' => fn ($query) => $query->whereHas('academicTask', fn ($task) => $task->where('academic_period_id', $period->id))->with('academicTask.subject')])->get() : collect();

        return Inertia::render('admin/grades', [
            'years' => $years->map(fn (AcademicYear $year): array => ['id' => $year->id, 'name' => $year->name, 'is_active' => $year->is_active]),
            'periods' => $periods->map(fn (AcademicPeriod $item): array => ['id' => $item->id, 'name' => $item->name, 'year' => $item->academicYear->name]),
            'selected_year_id' => $selectedYearId,
            'selected_period_id' => $period?->id,
            'students' => $enrollments->sortBy('student.last_name')->map(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->id,
                'name' => collect([$enrollment->student->first_name, $enrollment->student->middle_name, $enrollment->student->last_name, $enrollment->student->second_last_name])->filter()->join(' '),
                'course' => $enrollment->grade->name.' '.$enrollment->classroom?->name,
                'average' => round((float) $enrollment->taskGrades->avg('score'), 2),
                'subjects' => $enrollment->taskGrades->groupBy(fn ($grade) => $grade->academicTask->subject_id)->map(fn ($items): array => ['name' => $items->first()->academicTask->subject->name, 'score' => round((float) $items->avg('score'), 2)])->values(),
            ])->values(),
        ]);
    }
}
