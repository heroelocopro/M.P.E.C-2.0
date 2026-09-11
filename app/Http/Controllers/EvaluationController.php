<?php

namespace App\Http\Controllers;

use App\Actions\SyncEvaluationGrades;
use App\Enums\UserRole;
use App\Http\Requests\GradeEvaluationRequest;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\SubmitEvaluationRequest;
use App\Models\AcademicPeriod;
use App\Models\Evaluation;
use App\Models\EvaluationAttempt;
use App\Models\GradingScale;
use App\Models\TeacherAssignment;
use App\Notifications\SchoolAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    public function __construct(private SyncEvaluationGrades $syncGrades) {}

    public function grade(GradeEvaluationRequest $request, Evaluation $evaluation, EvaluationAttempt $attempt): RedirectResponse
    {
        $scale = GradingScale::current();
        $scores = collect($request->array('manual_scores'))->map(fn (mixed $score): float => $scale->toPercentage((float) $score))->all();
        DB::transaction(function () use ($attempt, $scores, $request, $evaluation): void {
            $attempt->update([
                'manual_scores' => $scores,
                'feedback' => $request->input('feedback'),
                'score' => round(($attempt->correct_count * 100 + array_sum($scores)) / count($evaluation->questions), 2),
                'graded_at' => now(),
            ]);
            $this->syncGrades->handle($evaluation);
        });

        return back();
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $isTeacher = $user->role === UserRole::Teacher;
        $scale = GradingScale::current();
        abort_unless($isTeacher || $user->role === UserRole::Student, 403);
        $assignments = TeacherAssignment::query()
            ->when($isTeacher, fn ($query) => $query->where('teacher_id', $user->id),
                fn ($query) => $query->whereHas('classroom.enrollments', fn ($enrollment) => $enrollment->where('status', 'activa')->whereHas('student', fn ($student) => $student->where('user_id', $user->id))))
            ->with(['subject', 'classroom.grade'])->get();

        return Inertia::render('evaluations', [
            'isTeacher' => $isTeacher,
            'assignments' => $assignments->map(fn ($assignment) => ['id' => $assignment->id, 'academic_year_id' => $assignment->classroom->academic_year_id, 'label' => "{$assignment->subject->name} · {$assignment->classroom->grade->name} {$assignment->classroom->name}"]),
            'periods' => AcademicPeriod::query()->whereIn('academic_year_id', $assignments->pluck('classroom.academic_year_id'))->orderBy('number')->get(['id', 'academic_year_id', 'name']),
            'evaluations' => Evaluation::query()->whereIn('teacher_assignment_id', $assignments->modelKeys())
                ->with(['attempts' => fn ($query) => $query->where('user_id', $user->id)])
                ->latest()->paginate(15)->withQueryString()->through(fn (Evaluation $evaluation): array => [
                    ...$evaluation->toArray(),
                    'attempts' => $evaluation->attempts->map(fn (EvaluationAttempt $attempt): array => $scale->presentAttempt($attempt)),
                ]),
        ]);
    }

    public function store(StoreEvaluationRequest $request): RedirectResponse
    {
        $evaluation = DB::transaction(function () use ($request): Evaluation {
            $evaluation = Evaluation::query()->create($request->safe()->except('academic_period_id'));
            $this->syncGrades->handle($evaluation, $request->integer('academic_period_id') ?: null);
            $assignment = $evaluation->teacherAssignment;
            $recipients = $assignment->classroom->enrollments()
                ->where('status', 'activa')
                ->with('student.user')
                ->get()
                ->pluck('student.user')
                ->filter(fn ($user): bool => $user?->role === UserRole::Student)
                ->unique('id');

            Notification::send($recipients, new SchoolAnnouncement(
                "Nueva evaluación: {$evaluation->title}",
                "{$assignment->subject->name} · Vence {$evaluation->due_at->translatedFormat('j \\d\\e F, H:i')}",
                'evaluacion',
                route('evaluations.show', $evaluation, absolute: false),
            ));

            return $evaluation;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Evaluación publicada y estudiantes notificados.']);

        return to_route('evaluations.show', $evaluation);
    }

    public function show(Request $request, Evaluation $evaluation): Response
    {
        $user = $request->user();
        $isTeacher = $user->role === UserRole::Teacher && $evaluation->teacherAssignment->teacher_id === $user->id;
        abort_unless($isTeacher || ($user->role === UserRole::Student && $evaluation->teacherAssignment->classroom->accessibleTo($user)), 403);
        $attempt = $evaluation->attempts()->where('user_id', $user->id)->first();
        $scale = GradingScale::current();

        return Inertia::render('evaluation', [
            'evaluation' => $evaluation,
            'isTeacher' => $isTeacher,
            'questions' => collect($evaluation->questions)->map(fn (array $question): array => $isTeacher ? $question : Arr::only($question, ['type', 'prompt', 'options'])),
            'attempt' => $attempt ? $scale->presentAttempt($attempt) : null,
            'isExpired' => $evaluation->due_at->isPast(),
            'results' => $isTeacher ? $evaluation->attempts()->with('user:id,name')->latest()->paginate(30)->withQueryString()->through(fn (EvaluationAttempt $attempt): array => $scale->presentAttempt($attempt)) : null,
        ]);
    }

    public function submit(SubmitEvaluationRequest $request, Evaluation $evaluation): RedirectResponse
    {
        DB::transaction(function () use ($request, $evaluation): void {
            $evaluation = Evaluation::query()->lockForUpdate()->findOrFail($evaluation->id);
            if ($evaluation->due_at->isPast()) {
                throw ValidationException::withMessages(['answers' => 'La evaluación ya venció.']);
            }
            if ($evaluation->attempts()->where('user_id', $request->user()->id)->exists()) {
                throw ValidationException::withMessages(['answers' => 'Ya enviaste esta evaluación. Solo se permite un intento.']);
            }
            $answers = $request->array('answers');
            $correct = 0;
            foreach ($evaluation->questions as $index => $question) {
                if ($question['type'] === 'choice' && isset($question['correct']) && (int) $answers[$index] === $question['correct']) {
                    $correct++;
                }
            }
            $evaluation->attempts()->create([
                'user_id' => $request->user()->id,
                'answers' => $answers,
                'correct_count' => $correct,
                'score' => round(100 * $correct / count($evaluation->questions), 2),
                'graded_at' => collect($evaluation->questions)->contains('type', 'open') ? null : now(),
            ]);
            $this->syncGrades->handle($evaluation);
        });

        return back();
    }
}
