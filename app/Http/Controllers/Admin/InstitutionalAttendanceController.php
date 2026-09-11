<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstitutionalAttendance;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionalAttendanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/institutional-attendance', [
            'records' => InstitutionalAttendance::query()->with(['student.user'])->whereDate('date', today())->latest('checked_in_at')->get()->map(fn (InstitutionalAttendance $record): array => [
                'id' => $record->id,
                'student' => $record->student->user?->name,
                'time' => $record->checked_in_at->format('H:i'),
                'method' => $record->method,
            ]),
        ]);
    }

    public function scan(Request $request, string $token): Response
    {
        $student = Student::query()->where('card_token', $token)->with(['user', 'latestEnrollment.grade', 'latestEnrollment.classroom'])->firstOrFail();
        $record = InstitutionalAttendance::query()->updateOrCreate(
            ['student_id' => $student->id, 'date' => today()],
            ['recorded_by_id' => $request->user()->id, 'checked_in_at' => now(), 'method' => 'qr'],
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => "Ingreso registrado para {$student->user?->name}."]);

        return Inertia::render('admin/institutional-attendance', [
            'scanned' => [
                'name' => $student->user?->name,
                'document' => $student->document_number,
                'course' => $student->latestEnrollment?->grade->name.' '.$student->latestEnrollment?->classroom?->name,
                'time' => $record->checked_in_at->format('H:i'),
            ],
            'records' => InstitutionalAttendance::query()->with(['student.user'])->whereDate('date', today())->latest('checked_in_at')->get()->map(fn (InstitutionalAttendance $item): array => [
                'id' => $item->id,
                'student' => $item->student->user?->name,
                'time' => $item->checked_in_at->format('H:i'),
                'method' => $item->method,
            ]),
        ]);
    }
}
