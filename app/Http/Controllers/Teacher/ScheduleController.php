<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ScheduleEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $entries = ScheduleEntry::query()
            ->whereBelongsTo($request->user(), 'teacher')
            ->with(['subject:id,name', 'classroom.grade:id,name', 'classroom.campus:id,name'])
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ScheduleEntry $entry): array => [
                'id' => $entry->id,
                'day_of_week' => $entry->day_of_week,
                'subject' => $entry->subject->name,
                'course' => "{$entry->classroom->grade->name} {$entry->classroom->name}",
                'campus' => $entry->classroom->campus->name,
                'starts_at' => mb_substr($entry->starts_at, 0, 5),
                'ends_at' => mb_substr($entry->ends_at, 0, 5),
                'room' => $entry->room,
            ]);

        return Inertia::render('teacher/schedule', ['entries' => $entries]);
    }
}
