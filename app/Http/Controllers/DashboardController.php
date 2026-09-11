<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AcademicTask;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\ScheduleEntry;
use App\Models\Student;
use App\Models\TaskGrade;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->role === UserRole::Administrator) {
            $academicYear = AcademicYear::query()->where('is_active', true)->first();
            $period = $academicYear ? AcademicPeriod::query()
                ->whereBelongsTo($academicYear)
                ->where('is_closed', false)
                ->whereDate('starts_at', '<=', today())
                ->whereDate('ends_at', '>=', today())
                ->orderBy('number')
                ->first() : null;
            $enrollmentQuery = Enrollment::query()
                ->where('status', EnrollmentStatus::Active)
                ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id), fn ($query) => $query->whereKey([]));
            $studentCount = (clone $enrollmentQuery)->count();
            $newStudents = (clone $enrollmentQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
            $gradeAverage = $period ? TaskGrade::query()
                ->whereHas('enrollment', fn ($query) => $query->where('academic_year_id', $academicYear->id))
                ->whereHas('academicTask', fn ($query) => $query->where('academic_period_id', $period->id))
                ->avg('score') : null;
            $attendanceQuery = AttendanceRecord::query()
                ->whereHas('enrollment', fn ($query) => $query->where('academic_year_id', $academicYear?->id))
                ->when($period, fn ($query) => $query->whereBetween('date', [$period->starts_at, $period->ends_at]));
            $attendanceTotal = (clone $attendanceQuery)->count();
            $attended = (clone $attendanceQuery)->whereIn('status', ['presente', 'tarde'])->count();

            return Inertia::render('dashboard', [
                'adminDashboard' => [
                    'student_count' => $studentCount,
                    'new_student_count' => $newStudents,
                    'average' => $gradeAverage === null ? null : round((float) $gradeAverage, 2),
                    'attendance_percentage' => $attendanceTotal === 0 ? null : round(100 * $attended / $attendanceTotal, 1),
                    'teacher_count' => User::query()->where('role', UserRole::Teacher)->count(),
                    'academic_year' => $academicYear?->name,
                    'period' => $period?->name,
                ],
            ]);
        }

        if ($user->role === UserRole::Teacher) {
            $assignments = TeacherAssignment::query()
                ->whereBelongsTo($user, 'teacher')
                ->with(['classroom.grade', 'classroom.campus', 'classroom.schoolShift', 'subject'])
                ->get();
            $classroomIds = $assignments->pluck('classroom_id')->unique();

            return Inertia::render('dashboard', [
                'teacherDashboard' => [
                    'course_count' => $assignments->unique('classroom_id')->count(),
                    'subject_count' => $assignments->unique('subject_id')->count(),
                    'student_count' => Enrollment::query()->whereIn('classroom_id', $classroomIds)->where('status', EnrollmentStatus::Active)->count(),
                    'active_task_count' => AcademicTask::query()->whereBelongsTo($user, 'assignedBy')->where('due_at', '>=', now())->count(),
                    'assignments' => $assignments->map(fn (TeacherAssignment $assignment): array => [
                        'id' => $assignment->id,
                        'subject' => $assignment->subject->name,
                        'course' => "{$assignment->classroom->grade->name} {$assignment->classroom->name}",
                        'campus' => $assignment->classroom->campus->name,
                        'shift' => $assignment->classroom->schoolShift->name,
                    ]),
                    'today_classes' => ScheduleEntry::query()
                        ->whereBelongsTo($user, 'teacher')
                        ->where('day_of_week', now()->dayOfWeekIso)
                        ->with(['subject', 'classroom.grade'])
                        ->orderBy('starts_at')
                        ->get()
                        ->map(fn (ScheduleEntry $entry): array => [
                            'id' => $entry->id,
                            'subject' => $entry->subject->name,
                            'course' => "{$entry->classroom->grade->name} {$entry->classroom->name}",
                            'starts_at' => mb_substr($entry->starts_at, 0, 5),
                            'ends_at' => mb_substr($entry->ends_at, 0, 5),
                            'room' => $entry->room,
                        ]),
                ],
            ]);
        }

        if ($user->role !== UserRole::Student) {
            return Inertia::render('dashboard');
        }

        $student = $user->student()
            ->with([
                'enrollments' => fn ($query) => $query
                    ->where('status', EnrollmentStatus::Active)
                    ->with(['academicYear', 'grade', 'classroom.campus', 'classroom.schoolShift'])
                    ->latest('enrolled_at'),
            ])
            ->first();

        $enrollment = $student?->enrollments->first();
        $taskQuery = AcademicTask::query()
            ->whereIn('type', ['tarea', 'proyecto'])
            ->whereNotNull('published_at')
            ->with('subject:id,name')
            ->orderBy('due_at');

        $enrollment?->classroom
            ? $taskQuery->whereBelongsTo($enrollment->classroom)
            : $taskQuery->whereKey([]);

        $tasks = $taskQuery->get();

        return Inertia::render('dashboard', [
            'studentDashboard' => [
                'student_name' => $student instanceof Student ? $student->first_name : $user->name,
                'institution_name' => config('app.name') === 'Laravel' ? 'Aula' : config('app.name'),
                'academic_year' => $enrollment?->academicYear->name,
                'grade' => $enrollment?->grade->name,
                'classroom' => $enrollment?->classroom?->name,
                'campus' => $enrollment?->classroom?->campus->name,
                'campus_address' => $enrollment?->classroom?->campus->address,
                'shift' => $enrollment?->classroom?->schoolShift->name,
                'enrollment_number' => $enrollment?->enrollment_number,
                'average' => null,
                'attendance_percentage' => null,
                'pending_assignments' => $tasks->where('due_at', '>=', now())->count(),
                'recent_activity' => $tasks->take(4)->map(fn (AcademicTask $task): array => [
                    'id' => $task->id,
                    'type' => $task->type === 'proyecto' ? 'project' : 'assignment',
                    'title' => $task->title,
                    'detail' => $task->subject->name,
                    'date' => $task->due_at->toIso8601String(),
                    'href' => route('student.tasks', ['task' => $task->id], absolute: false),
                ])->values(),
            ],
        ]);
    }
}
