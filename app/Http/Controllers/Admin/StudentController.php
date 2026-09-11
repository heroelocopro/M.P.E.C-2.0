<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\InstitutionalAttendance;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function active(Request $request): Response
    {
        return $this->render($request, StudentStatus::Active);
    }

    public function graduates(Request $request): Response
    {
        return $this->render($request, StudentStatus::Graduated);
    }

    public function show(Student $student): Response
    {
        $student->card_token ??= (string) Str::uuid();
        $student->save();
        $student->load(['user', 'medicalProfile', 'institutionalAttendances' => fn ($query) => $query->latest('date')->limit(10), 'enrollments' => fn ($query) => $query
            ->with(['academicYear.periods', 'grade', 'classroom.campus', 'classroom.schoolShift', 'taskGrades.academicTask.subject', 'taskGrades.academicTask.academicPeriod'])
            ->orderByDesc('enrolled_at')]);

        return Inertia::render('admin/students/show', [
            'student' => [
                'id' => $student->id,
                'name' => $student->user?->name,
                'email' => $student->user?->email,
                'document_type' => $student->document_type->label(),
                'document_number' => $student->document_number,
                'birth_date' => $student->birth_date->toDateString(),
                'status' => $student->status->value,
                'graduated_at' => $student->graduated_at?->toDateString(),
                'card_url' => route('admin.institutional-attendance.scan', $student->card_token),
                'medical' => $student->medicalProfile,
                'institutional_attendances' => $student->institutionalAttendances->map(fn (InstitutionalAttendance $attendance): array => [
                    'date' => $attendance->date->toDateString(),
                    'time' => $attendance->checked_in_at->format('H:i'),
                    'method' => $attendance->method,
                ]),
                'enrollments' => $student->enrollments->map(fn (Enrollment $enrollment): array => [
                    'id' => $enrollment->id,
                    'number' => $enrollment->enrollment_number,
                    'year' => $enrollment->academicYear->name,
                    'grade' => $enrollment->grade->name,
                    'campus' => $enrollment->classroom?->campus->name,
                    'shift' => $enrollment->classroom?->schoolShift->name,
                    'status' => $enrollment->status->value,
                    'enrolled_at' => $enrollment->enrolled_at->toDateString(),
                    'completed_at' => $enrollment->completed_at?->toDateString(),
                    'observations' => $enrollment->observations,
                    'periods' => $enrollment->academicYear->periods->map(function ($period) use ($enrollment): array {
                        $grades = $enrollment->taskGrades->filter(fn ($taskGrade) => $taskGrade->academicTask->academic_period_id === $period->id);
                        $subjects = $grades->groupBy(fn ($taskGrade) => $taskGrade->academicTask->subject_id)->map(fn ($items): array => [
                            'name' => (string) $items->first()->academicTask->subject->name,
                            'score' => round((float) $items->avg('score'), 2),
                        ])->values();

                        return [
                            'id' => $period->id,
                            'name' => (string) $period->name,
                            'subjects' => $subjects->all(),
                            'average' => $subjects->isEmpty() ? null : round((float) $subjects->avg('score'), 2),
                        ];
                    }),
                ]),
            ],
        ]);
    }

    public function updateMedical(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'blood_type' => ['nullable', 'string', 'max:10'],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'medical_conditions' => ['nullable', 'string', 'max:2000'],
            'disabilities' => ['nullable', 'string', 'max:2000'],
            'health_provider' => ['nullable', 'string', 'max:160'],
            'emergency_contact_name' => ['nullable', 'string', 'max:160'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:80'],
            'gender_identity' => ['nullable', 'string', 'max:80'],
            'sexual_orientation' => ['nullable', 'string', 'max:80'],
            'ethnic_identity' => ['nullable', 'string', 'max:100'],
            'indigenous_people' => ['nullable', 'string', 'max:120'],
            'data_consent' => ['boolean'],
        ]);
        $student->medicalProfile()->updateOrCreate([], $data);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Información sensible del estudiante actualizada.']);

        return back();
    }

    public function email(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);
        $student->load('user');
        abort_unless($student->user?->email !== null, 422, 'El estudiante no tiene correo registrado.');
        Mail::raw($data['message'], function ($mail) use ($student, $data): void {
            $mail->to($student->user->email)->subject($data['subject']);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Correo enviado al estudiante.']);

        return back();
    }

    private function render(Request $request, StudentStatus $status): Response
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($validated['search'] ?? '');

        $students = Student::query()
            ->with(['user', 'latestEnrollment.academicYear', 'latestEnrollment.grade'])
            ->withCount('enrollments')
            ->where('status', $status)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $studentQuery) use ($search): void {
                $studentQuery->where('document_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }))
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->user?->name,
                'email' => $student->user?->email,
                'document_type' => $student->document_type->label(),
                'document_number' => $student->document_number,
                'grade' => $student->latestEnrollment?->grade->name,
                'year' => $student->latestEnrollment?->academicYear->name,
                'enrollments_count' => $student->enrollments_count,
                'graduated_at' => $student->graduated_at?->toDateString(),
            ]);

        return Inertia::render('admin/students/index', [
            'students' => $students,
            'mode' => $status === StudentStatus::Graduated ? 'graduates' : 'active',
            'filters' => ['search' => $search],
        ]);
    }
}
