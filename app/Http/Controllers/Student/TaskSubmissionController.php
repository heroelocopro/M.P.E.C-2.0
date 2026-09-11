<?php

namespace App\Http\Controllers\Student;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreTaskSubmissionRequest;
use App\Models\AcademicTask;
use App\Models\AcademicTaskSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskSubmissionController extends Controller
{
    public function store(StoreTaskSubmissionRequest $request, AcademicTask $academicTask): RedirectResponse
    {
        $enrollment = $request->user()->student?->enrollments()
            ->where('classroom_id', $academicTask->classroom_id)
            ->where('status', 'activa')
            ->latest('enrolled_at')
            ->firstOrFail();
        $disk = (string) config('filesystems.school_files');
        $oldSubmission = $academicTask->submissions()->whereBelongsTo($enrollment)->first();
        $path = $request->file('file')->store("academic-submissions/{$academicTask->id}/{$enrollment->id}", $disk);
        abort_if($path === false, 500, 'No fue posible almacenar la entrega.');

        try {
            DB::transaction(function () use ($academicTask, $enrollment, $request, $disk, $path): void {
                $academicTask->submissions()->updateOrCreate(
                    ['enrollment_id' => $enrollment->id],
                    [
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => $request->file('file')->getClientOriginalName(),
                        'mime_type' => $request->file('file')->getMimeType() ?? 'application/octet-stream',
                        'size' => $request->file('file')->getSize(),
                        'submitted_at' => now(),
                    ],
                );
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        if ($oldSubmission) {
            Storage::disk($oldSubmission->disk)->delete($oldSubmission->path);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $oldSubmission
                ? 'Tu entrega fue actualizada correctamente.'
                : 'Tu entrega fue enviada correctamente.',
        ]);

        return back();
    }

    public function download(Request $request, AcademicTaskSubmission $submission): StreamedResponse
    {
        $submission->load(['academicTask', 'enrollment.student']);
        $user = $request->user();
        $isStudentOwner = $user->role === UserRole::Student
            && $submission->enrollment->student->user_id === $user->id;
        $isTeacherOwner = $user->role === UserRole::Teacher
            && $user->teacherAssignments()
                ->where('classroom_id', $submission->academicTask->classroom_id)
                ->where('subject_id', $submission->academicTask->subject_id)
                ->exists();

        abort_unless($isStudentOwner || $isTeacherOwner || $user->role === UserRole::Administrator, 403);

        $stream = Storage::disk($submission->disk)->readStream($submission->path);
        abort_unless(is_resource($stream), 404);

        return response()->streamDownload(
            function () use ($stream): void {
                fpassthru($stream);
                fclose($stream);
            },
            $submission->original_name,
            ['Content-Type' => $submission->mime_type],
        );
    }
}
