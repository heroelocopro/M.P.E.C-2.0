<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\TeacherAssignment;
use App\Notifications\SchoolAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $classrooms = TeacherAssignment::query()
            ->whereBelongsTo($request->user(), 'teacher')
            ->with(['classroom.campus', 'classroom.grade', 'classroom.schoolShift'])
            ->get()
            ->unique('classroom_id')
            ->map(fn (TeacherAssignment $assignment): array => [
                'id' => $assignment->classroom->id,
                'name' => "{$assignment->classroom->grade->name} {$assignment->classroom->name}",
                'detail' => "{$assignment->classroom->campus->name} · {$assignment->classroom->schoolShift->name}",
            ])->values();

        $selectedClassroomId = $request->integer('classroom') ?: $classrooms->first()['id'] ?? null;
        abort_if($selectedClassroomId && ! $classrooms->contains('id', $selectedClassroomId), 403);
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();

        $students = $selectedClassroomId
            ? Enrollment::query()
                ->where('classroom_id', $selectedClassroomId)
                ->where('status', 'activa')
                ->with(['student', 'attendanceRecords' => fn ($query) => $query->whereDate('date', $date)])
                ->get()
                ->sortBy('student.last_name')
                ->map(fn (Enrollment $enrollment): array => [
                    'student_id' => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'name' => collect([$enrollment->student->first_name, $enrollment->student->middle_name, $enrollment->student->last_name, $enrollment->student->second_last_name])->filter()->join(' '),
                    'document_number' => $enrollment->student->document_number,
                    'status' => $enrollment->attendanceRecords->isEmpty()
                        ? 'presente'
                        : $enrollment->attendanceRecords->first()->status,
                    'notes' => $enrollment->attendanceRecords->isEmpty()
                        ? null
                        : $enrollment->attendanceRecords->first()->notes,
                ])->values()
            : collect();

        return Inertia::render('teacher/attendance', [
            'classrooms' => $classrooms,
            'selected_classroom_id' => $selectedClassroomId,
            'date' => $date,
            'students' => $students,
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $enrollments = Enrollment::query()
            ->where('classroom_id', $data['classroom_id'])
            ->whereKey(array_keys($data['attendance']))
            ->get();

        abort_if($enrollments->count() !== count($data['attendance']), 422);

        DB::transaction(function () use ($data, $enrollments, $request): void {
            $timestamp = now();
            $attendanceDate = Carbon::parse($data['date'])->startOfDay();
            $records = [];

            foreach ($enrollments as $enrollment) {
                $records[] = [
                    'enrollment_id' => $enrollment->id,
                    'date' => $attendanceDate,
                    'recorded_by_id' => $request->user()->id,
                    'status' => $data['attendance'][$enrollment->id],
                    'notes' => $data['notes'][$enrollment->id] ?? null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            AttendanceRecord::query()->upsert(
                $records,
                ['enrollment_id', 'date'],
                ['recorded_by_id', 'status', 'notes', 'updated_at'],
            );
        });

        $enrollments->load(['student.user', 'student.guardians']);
        foreach ($enrollments as $enrollment) {
            $status = $data['attendance'][$enrollment->id];
            if (! in_array($status, ['ausente', 'tarde', 'excusa'], true)) {
                continue;
            }
            $recipients = collect([$enrollment->student->user])->merge($enrollment->student->guardians)->filter()->unique('id');
            Notification::send($recipients, new SchoolAnnouncement(
                'Novedad de asistencia',
                "Se registró {$status} para {$enrollment->student->first_name} el {$data['date']}.",
                'asistencia',
                route('notifications', absolute: false),
            ));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asistencia guardada correctamente.']);

        return back();
    }
}
