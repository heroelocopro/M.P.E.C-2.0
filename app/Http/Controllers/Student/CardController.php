<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $student = $request->user()->student;
        abort_unless($student !== null, 404);
        $student->card_token ??= (string) Str::uuid();
        $student->save();

        return Inertia::render('student/card', [
            'student' => [
                'name' => collect([$student->first_name, $student->middle_name, $student->last_name, $student->second_last_name])->filter()->join(' '),
                'document' => $student->document_number,
                'card_url' => route('admin.institutional-attendance.scan', $student->card_token),
            ],
        ]);
    }
}
