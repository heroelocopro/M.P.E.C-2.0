<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreStudentReportRequest;
use App\Models\Student;
use App\Notifications\SchoolAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class StudentReportController extends Controller
{
    public function index(Request $request): Response
    {
        $classroomIds = $request->user()->teacherAssignments()->pluck('classroom_id')->unique();
        $students = Student::query()
            ->whereHas('enrollments', fn ($query) => $query->whereIn('classroom_id', $classroomIds)->where('status', 'activa'))
            ->with(['user:id,name', 'latestEnrollment.grade', 'latestEnrollment.classroom'])
            ->orderBy('last_name')->get();

        return Inertia::render('teacher/student-reports', ['students' => $students->map(fn ($student): array => [
            'id' => $student->id,
            'name' => $student->user?->name,
            'course' => $student->latestEnrollment?->grade->name.' '.$student->latestEnrollment?->classroom?->name,
        ])]);
    }

    public function store(StoreStudentReportRequest $request, Student $student): RedirectResponse
    {
        $report = $student->reports()->create([...$request->validated(), 'reported_by_id' => $request->user()->id, 'reported_at' => now()]);
        $recipients = collect([$student->user])->merge($student->guardians)->filter()->unique('id');
        Notification::send($recipients, new SchoolAnnouncement(
            $report->title, $report->details, 'reporte', route('notifications', absolute: false),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reporte enviado al estudiante y sus acudientes.']);

        return back();
    }
}
