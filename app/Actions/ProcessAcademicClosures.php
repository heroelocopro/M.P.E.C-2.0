<?php

namespace App\Actions;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Mail\AnnualAcademicResultMail;
use App\Models\AcademicPeriod;
use App\Models\AcademicPeriodReminder;
use App\Models\AcademicYearResult;
use App\Models\Enrollment;
use App\Models\GradingScale;
use App\Models\PeriodFinalGrade;
use App\Models\User;
use App\Notifications\SchoolAnnouncement;
use App\Services\SimplePdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class ProcessAcademicClosures
{
    public function __construct(private SimplePdf $pdf) {}

    /** @return array{reminders: int, periods_closed: int, years_closed: int} */
    public function handle(?Carbon $processingDate = null): array
    {
        $date = ($processingDate ?? today())->startOfDay();
        $reminders = 0;
        foreach ([3, 2, 1] as $daysBefore) {
            AcademicPeriod::query()
                ->where('is_closed', false)
                ->whereDate('ends_at', $date->copy()->addDays($daysBefore))
                ->get()
                ->each(function (AcademicPeriod $period) use ($daysBefore, &$reminders): void {
                    if ($this->sendReminder($period, $daysBefore)) {
                        $reminders++;
                    }
                });
        }

        $periodsClosed = 0;
        $yearsClosed = 0;
        AcademicPeriod::query()
            ->where('is_closed', false)
            ->whereDate('ends_at', '<=', $date)
            ->orderBy('academic_year_id')
            ->orderBy('number')
            ->get()
            ->each(function (AcademicPeriod $period) use (&$periodsClosed, &$yearsClosed): void {
                $closedYear = $this->closePeriod($period);
                $periodsClosed++;
                $yearsClosed += $closedYear ? 1 : 0;
            });

        return [
            'reminders' => $reminders,
            'periods_closed' => $periodsClosed,
            'years_closed' => $yearsClosed,
        ];
    }

    private function sendReminder(AcademicPeriod $period, int $daysBefore): bool
    {
        $reminder = AcademicPeriodReminder::query()->firstOrCreate(
            ['academic_period_id' => $period->id, 'days_before' => $daysBefore],
            ['sent_at' => now()],
        );
        if (! $reminder->wasRecentlyCreated) {
            return false;
        }

        Notification::send(
            $this->periodRecipients($period),
            new SchoolAnnouncement(
                "Cierre de {$period->name} en {$daysBefore} ".($daysBefore === 1 ? 'día' : 'días'),
                'Revisa y completa las calificaciones pendientes antes del cierre automático.',
                'cierre_academico',
                '/notificaciones',
            ),
        );

        return true;
    }

    public function closePeriod(AcademicPeriod $period): bool
    {
        return DB::transaction(function () use ($period): bool {
            $period = AcademicPeriod::query()->lockForUpdate()->findOrFail($period->id);
            if ($period->is_closed) {
                return false;
            }

            $enrollments = Enrollment::query()
                ->where('academic_year_id', $period->academic_year_id)
                ->with(['taskGrades' => fn ($query) => $query
                    ->whereHas('academicTask', fn ($tasks) => $tasks->where('academic_period_id', $period->id))
                    ->with('academicTask.subject')])
                ->get();

            foreach ($enrollments as $enrollment) {
                $enrollment->taskGrades
                    ->groupBy(fn ($grade) => $grade->academicTask->subject_id)
                    ->each(function (Collection $grades, int $subjectId) use ($enrollment, $period): void {
                        PeriodFinalGrade::query()->updateOrCreate(
                            ['enrollment_id' => $enrollment->id, 'academic_period_id' => $period->id, 'subject_id' => $subjectId],
                            [
                                'final_score' => round((float) $grades->avg('score'), 2),
                                'activities_count' => $grades->count(),
                                'activity_breakdown' => $grades->map(fn ($grade): array => [
                                    'task_id' => $grade->academic_task_id,
                                    'title' => $grade->academicTask->title,
                                    'type' => $grade->academicTask->type,
                                    'score' => (float) $grade->score,
                                ])->values()->all(),
                                'closed_at' => now(),
                            ],
                        );
                    });
            }

            $period->update(['is_closed' => true]);
            Notification::send($this->periodRecipients($period), new SchoolAnnouncement(
                "{$period->name} cerrado",
                'Las notas finales del periodo fueron consolidadas y ya no pueden modificarse.',
                'cierre_academico',
                '/notificaciones',
            ));

            $hasOpenPeriod = AcademicPeriod::query()
                ->where('academic_year_id', $period->academic_year_id)
                ->where('is_closed', false)
                ->exists();

            if ($hasOpenPeriod) {
                return false;
            }

            $this->closeAcademicYear($period);

            return true;
        });
    }

    private function closeAcademicYear(AcademicPeriod $lastPeriod): void
    {
        $scale = GradingScale::current();
        $enrollments = Enrollment::query()
            ->where('academic_year_id', $lastPeriod->academic_year_id)
            ->where('status', EnrollmentStatus::Active)
            ->with(['student.user', 'student.guardians', 'grade', 'academicYear', 'periodFinalGrades.subject'])
            ->get();

        foreach ($enrollments as $enrollment) {
            $subjectResults = [];
            foreach ($enrollment->periodFinalGrades->groupBy('subject_id') as $grades) {
                $firstGrade = $grades->firstOrFail();
                $periodResults = [];
                foreach ($grades->sortBy(fn (PeriodFinalGrade $grade): int => $grade->academicPeriod->number) as $grade) {
                    $periodResults[] = [
                        'period_id' => $grade->academic_period_id,
                        'score' => $grade->final_score,
                    ];
                }
                $subjectResults[] = [
                    'subject_id' => $firstGrade->subject_id,
                    'name' => $firstGrade->subject->name,
                    'score' => round((float) $grades->avg('final_score'), 2),
                    'periods' => $periodResults,
                ];
            }
            $subjects = collect($subjectResults);
            $average = $subjects->isEmpty() ? null : round((float) $subjects->avg('score'), 2);
            $passed = $average !== null && $average >= $scale->passing;
            $outcome = $passed ? ($enrollment->grade->is_final ? 'graduated' : 'promoted') : ($average === null ? 'no_grades' : 'not_promoted');

            AcademicYearResult::query()->updateOrCreate(
                ['enrollment_id' => $enrollment->id],
                ['final_average' => $average, 'passed' => $passed, 'outcome' => $outcome, 'subject_results' => $subjects->all(), 'closed_at' => now()],
            );
            $enrollment->update(['status' => EnrollmentStatus::Completed, 'completed_at' => now()]);
            if ($outcome === 'graduated') {
                $enrollment->student->update(['status' => StudentStatus::Graduated, 'graduated_at' => today()]);
            }

            $this->notifyAnnualResult($enrollment, $average, $outcome, $subjects, $scale, $lastPeriod);
        }

        $lastPeriod->academicYear()->update(['is_active' => false]);
    }

    /** @param Collection<int, array{subject_id: int, name: string, score: float, periods: list<array{period_id: int, score: float}>}> $subjects */
    private function notifyAnnualResult(Enrollment $enrollment, ?float $average, string $outcome, Collection $subjects, GradingScale $scale, AcademicPeriod $lastPeriod): void
    {
        $congratulations = match ($outcome) {
            'graduated' => '¡Felicitaciones por tu graduación!',
            'promoted' => '¡Felicitaciones! Aprobaste el año académico.',
            'not_promoted' => 'El año académico finalizó. Consulta tu resultado y el plan de seguimiento.',
            default => 'El año académico finalizó sin calificaciones consolidadas.',
        };
        $recipients = collect([$enrollment->student->user])->merge($enrollment->student->guardians)->filter()->unique('id');
        Notification::send($recipients, new SchoolAnnouncement(
            $congratulations,
            $average === null ? 'No hay promedio final disponible.' : "Tu promedio final es {$average}.",
            'resultado_anual',
            '/estudiante/notas',
        ));

        $report = [
            'student' => collect([$enrollment->student->first_name, $enrollment->student->middle_name, $enrollment->student->last_name, $enrollment->student->second_last_name])->filter()->join(' '),
            'year' => $enrollment->academicYear->name,
            'grade' => $enrollment->grade->name.' '.$enrollment->classroom?->name,
            'subjects' => $subjects->map(fn (array $subject): array => ['name' => $subject['name'], 'score' => $subject['score'], 'activities' => count($subject['periods'])])->all(),
            'average' => $average ?? 0,
        ];
        $pdf = $this->pdf->make(['report' => $report, 'period' => $lastPeriod, 'institution_name' => config('app.name'), 'gradingScale' => $scale->limits()]);
        $filename = "reporte-final-{$enrollment->academicYear->name}.pdf";
        foreach ($recipients as $recipient) {
            $email = $recipient->personal_email ?: $recipient->email;
            Mail::to($email)->send(new AnnualAcademicResultMail(
                $congratulations,
                $average,
                $enrollment->academicYear->name,
                $pdf,
                $filename,
            ));
        }
    }

    /** @return Collection<int, User> */
    private function periodRecipients(AcademicPeriod $period): Collection
    {
        $enrollments = Enrollment::query()
            ->where('academic_year_id', $period->academic_year_id)
            ->where('status', EnrollmentStatus::Active)
            ->with(['student.user', 'student.guardians'])
            ->get();
        $studentsAndGuardians = $enrollments->flatMap(fn (Enrollment $enrollment): array => [
            $enrollment->student->user,
            ...$enrollment->student->guardians->all(),
        ]);
        $teachers = User::query()->where('role', UserRole::Teacher)->whereHas('teacherAssignments.classroom', fn ($classrooms) => $classrooms->where('academic_year_id', $period->academic_year_id))->get();
        $administrators = User::query()->where('role', UserRole::Administrator)->get();

        return $studentsAndGuardians->merge($teachers)->merge($administrators)->filter()->unique('id')->values();
    }
}
