<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\AcademicTaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcademicTaskAttachmentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, AcademicTaskAttachment $academicTaskAttachment): StreamedResponse
    {
        $user = $request->user();
        $task = $academicTaskAttachment->academicTask;
        $canDownload = $user->role === UserRole::Administrator
            || ($user->role === UserRole::Teacher && $user->teacherAssignments()
                ->where('classroom_id', $task->classroom_id)
                ->where('subject_id', $task->subject_id)
                ->exists())
            || ($user->role === UserRole::Student && $user->student?->enrollments()
                ->where('classroom_id', $task->classroom_id)
                ->where('status', EnrollmentStatus::Active)
                ->exists());

        abort_unless($canDownload, 403);
        abort_unless(Storage::disk($academicTaskAttachment->disk)->exists($academicTaskAttachment->path), 404);

        return Storage::disk($academicTaskAttachment->disk)->download(
            $academicTaskAttachment->path,
            $academicTaskAttachment->original_name,
            ['Content-Type' => $academicTaskAttachment->mime_type],
        );
    }
}
