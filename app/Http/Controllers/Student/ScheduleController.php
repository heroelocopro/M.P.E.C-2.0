<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ScheduleEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $student = $request->user()->student;
        $enrollment = $student?->enrollments()
            ->whereNotNull('classroom_id')
            ->where('status', 'activa')
            ->with(['grade', 'classroom.campus', 'classroom.schoolShift'])
            ->latest('enrolled_at')
            ->first();

        $entries = $enrollment
            ? ScheduleEntry::query()
                ->whereBelongsTo($enrollment->classroom)
                ->with(['subject:id,name', 'teacher:id,name'])
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get()
                ->map(fn (ScheduleEntry $entry): array => [
                    'id' => $entry->id,
                    'day_of_week' => $entry->day_of_week,
                    'subject' => $entry->subject->name,
                    'teacher' => $entry->teacher?->name,
                    'starts_at' => mb_substr($entry->starts_at, 0, 5),
                    'ends_at' => mb_substr($entry->ends_at, 0, 5),
                    'room' => $entry->room,
                ])
            : collect();

        return Inertia::render('student/schedule', [
            'course' => $enrollment ? "{$enrollment->grade->name} {$enrollment->classroom->name}" : null,
            'campus' => $enrollment?->classroom->campus->name,
            'shift' => $enrollment?->classroom->schoolShift->name,
            'entries' => $entries,
        ]);
    }
}
