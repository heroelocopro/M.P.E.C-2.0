<?php

namespace Database\Seeders;

use App\Actions\SyncEvaluationGrades;
use App\Enums\DocumentType;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AcademicTask;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Evaluation;
use App\Models\EvaluationAttempt;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\Grade;
use App\Models\ScheduleEntry;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\Subject;
use App\Models\TaskGrade;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\SchoolAnnouncement;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Administrador Demo', 'email' => 'admin@aula.test', 'role' => UserRole::Administrator],
            ['name' => 'Profesor Demo', 'email' => 'profesor@aula.test', 'role' => UserRole::Teacher],
            ['name' => 'Estudiante Demo', 'email' => 'estudiante@aula.test', 'role' => UserRole::Student],
            ['name' => 'Acudiente Demo', 'email' => 'acudiente@aula.test', 'role' => UserRole::Guardian],
        ];

        foreach ($users as $userData) {
            $user = User::query()->firstOrNew([
                'email' => $userData['email'],
            ]);

            $user->forceFill([
                'name' => $userData['name'],
                'role' => $userData['role'],
                'email_verified_at' => now(),
                'password' => 'password',
            ])->save();
        }

        $studentUser = User::query()->where('email', 'estudiante@aula.test')->sole();
        $student = Student::query()->updateOrCreate(
            ['document_type' => DocumentType::IdentityCard, 'document_number' => '1000000001'],
            [
                'user_id' => $studentUser->id,
                'first_name' => 'Estudiante',
                'middle_name' => null,
                'last_name' => 'Demo',
                'second_last_name' => null,
                'birth_date' => '2013-05-15',
                'gender' => null,
                'phone' => '3000000000',
                'address' => 'Dirección de ejemplo',
                'status' => StudentStatus::Active,
                'graduated_at' => null,
            ],
        );
        $guardian = User::query()->where('email', 'acudiente@aula.test')->sole();
        $student->guardians()->syncWithoutDetaching([$guardian->id => ['relationship' => 'Acudiente']]);

        $enrollments = [
            ['number' => 'MAT-2025-DEMO', 'year' => '2025', 'grade' => '05', 'status' => EnrollmentStatus::Completed, 'date' => '2025-01-20'],
            ['number' => 'MAT-2026-DEMO', 'year' => '2026', 'grade' => '06', 'status' => EnrollmentStatus::Active, 'date' => '2026-01-19'],
        ];

        foreach ($enrollments as $enrollment) {
            $academicYear = AcademicYear::query()->where('name', $enrollment['year'])->firstOrFail();
            $grade = Grade::query()->where('code', $enrollment['grade'])->firstOrFail();
            $classroomId = Classroom::query()
                ->whereBelongsTo($academicYear)
                ->whereBelongsTo($grade)
                ->where('name', 'A')
                ->value('id');

            $student->enrollments()->updateOrCreate(
                ['academic_year_id' => $academicYear->id],
                [
                    'enrollment_number' => $enrollment['number'],
                    'grade_id' => $grade->id,
                    'classroom_id' => $classroomId,
                    'enrolled_at' => $enrollment['date'],
                    'status' => $enrollment['status'],
                    'completed_at' => $enrollment['status'] === EnrollmentStatus::Completed ? '2025-11-28 12:00:00' : null,
                ],
            );
        }

        $classroom = Classroom::query()
            ->whereBelongsTo(AcademicYear::query()->where('name', '2026')->firstOrFail())
            ->whereBelongsTo(Grade::query()->where('code', '06')->firstOrFail())
            ->with(['campus', 'schoolShift'])
            ->firstOrFail();
        $period = AcademicPeriod::query()->firstOrCreate(
            ['academic_year_id' => $classroom->academic_year_id, 'number' => 1],
            ['name' => 'Periodo 1', 'starts_at' => '2026-01-20', 'ends_at' => '2026-03-31'],
        );
        $teacher = User::query()->where('email', 'profesor@aula.test')->sole();
        $subjects = Subject::query()->whereIn('code', ['MAT', 'LEN', 'CIE', 'ING'])->get()->keyBy('code');

        foreach ($subjects as $subject) {
            TeacherAssignment::query()->firstOrCreate([
                'teacher_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'subject_id' => $subject->id,
            ]);
        }

        Announcement::query()->updateOrCreate(
            ['title' => 'Bienvenida al año lectivo 2026'],
            [
                'author_id' => User::query()->where('email', 'admin@aula.test')->value('id'),
                'kind' => 'institucional',
                'body' => 'La institución da la bienvenida a toda la comunidad educativa. Este comunicado es visible para todos los roles.',
                'published_at' => now(),
            ],
        );

        $teacherAnnouncement = Announcement::query()->updateOrCreate(
            ['title' => 'Material para la próxima clase'],
            [
                'author_id' => $teacher->id,
                'kind' => 'docente',
                'body' => 'Recuerden revisar las actividades pendientes antes de nuestra próxima clase.',
                'published_at' => now(),
            ],
        );
        $teacherAnnouncement->classrooms()->sync([$classroom->id]);

        foreach (
            [
                ['code' => 'MAT', 'title' => 'Ejercicios de fracciones', 'type' => 'tarea', 'days' => 3],
                ['code' => 'CIE', 'title' => 'Proyecto del ecosistema local', 'type' => 'proyecto', 'days' => 12],
                ['code' => 'LEN', 'title' => 'Lectura del capítulo cuatro', 'type' => 'tarea', 'days' => 6],
            ] as $task
        ) {
            AcademicTask::query()->updateOrCreate(
                ['classroom_id' => $classroom->id, 'title' => $task['title']],
                [
                    'subject_id' => $subjects[$task['code']]->id,
                    'academic_period_id' => $period->id,
                    'assigned_by_id' => $teacher->id,
                    'description' => 'Revisa las indicaciones vistas en clase y prepara la entrega para la fecha indicada.',
                    'type' => $task['type'],
                    'due_at' => now()->addDays($task['days'])->setTime(23, 59),
                    'published_at' => now(),
                ],
            );
        }

        $activeEnrollment = $student->enrollments()->where('status', EnrollmentStatus::Active)->sole();
        $gradedTask = AcademicTask::query()
            ->where('classroom_id', $classroom->id)
            ->where('title', 'Ejercicios de fracciones')
            ->sole();
        TaskGrade::query()->updateOrCreate(
            ['academic_task_id' => $gradedTask->id, 'enrollment_id' => $activeEnrollment->id],
            [
                'graded_by_id' => $teacher->id,
                'score' => 4.50,
                'observations' => 'Buen dominio del procedimiento y presentación ordenada.',
            ],
        );

        $periodScores = [1 => 3.80, 2 => 4.10, 3 => 4.30, 4 => 4.60];
        foreach (AcademicPeriod::query()->where('academic_year_id', $classroom->academic_year_id)->orderBy('number')->get() as $academicPeriod) {
            $periodTask = AcademicTask::query()->updateOrCreate(
                ['classroom_id' => $classroom->id, 'title' => "Actividad calificada · {$academicPeriod->name}"],
                [
                    'subject_id' => $subjects['MAT']->id,
                    'academic_period_id' => $academicPeriod->id,
                    'assigned_by_id' => $teacher->id,
                    'description' => 'Actividad de demostración para visualizar el historial y los promedios académicos.',
                    'type' => 'tarea',
                    'due_at' => $academicPeriod->ends_at->endOfDay(),
                    'published_at' => $academicPeriod->starts_at->startOfDay(),
                ],
            );

            TaskGrade::query()->updateOrCreate(
                ['academic_task_id' => $periodTask->id, 'enrollment_id' => $activeEnrollment->id],
                ['graded_by_id' => $teacher->id, 'score' => $periodScores[$academicPeriod->number], 'observations' => 'Calificación de demostración.'],
            );
        }

        foreach ([
            ['days' => 5, 'status' => 'presente', 'notes' => null],
            ['days' => 4, 'status' => 'presente', 'notes' => null],
            ['days' => 3, 'status' => 'tarde', 'notes' => 'Llegó diez minutos tarde.'],
            ['days' => 2, 'status' => 'ausente', 'notes' => 'Ausencia notificada al acudiente.'],
            ['days' => 1, 'status' => 'excusa', 'notes' => 'Incapacidad médica presentada.'],
        ] as $attendance) {
            AttendanceRecord::query()->updateOrCreate(
                ['enrollment_id' => $activeEnrollment->id, 'date' => now()->subDays($attendance['days'])->startOfDay()],
                ['recorded_by_id' => $teacher->id, 'status' => $attendance['status'], 'notes' => $attendance['notes']],
            );
        }

        StudentReport::query()->updateOrCreate(
            ['student_id' => $student->id, 'title' => 'Seguimiento académico positivo'],
            [
                'reported_by_id' => $teacher->id,
                'category' => 'academico',
                'details' => 'El estudiante participa activamente y entrega sus actividades a tiempo.',
                'reported_at' => now()->subDay(),
            ],
        );

        $forumTopic = ForumTopic::query()->updateOrCreate(
            ['classroom_id' => $classroom->id, 'title' => '¿Cómo podemos cuidar nuestro entorno?'],
            ['user_id' => $teacher->id, 'body' => 'Comparte una acción sencilla que podamos aplicar desde el colegio o desde casa.'],
        );
        ForumReply::query()->updateOrCreate(
            ['forum_topic_id' => $forumTopic->id, 'user_id' => $studentUser->id],
            ['body' => 'Podemos separar los residuos y reducir el uso de plástico en el salón.'],
        );

        $mathAssignment = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('classroom_id', $classroom->id)
            ->where('subject_id', $subjects['MAT']->id)
            ->sole();
        $evaluation = Evaluation::query()->updateOrCreate(
            ['teacher_assignment_id' => $mathAssignment->id, 'title' => 'Evaluación diagnóstica de matemáticas'],
            [
                'description' => 'Evaluación mixta de demostración con una pregunta automática y una abierta.',
                'questions' => [
                    ['type' => 'choice', 'prompt' => '¿Cuánto es 3 × 4?', 'options' => ['7', '10', '12', '14'], 'correct' => 2],
                    ['type' => 'open', 'prompt' => 'Explica con tus palabras cómo resolviste la multiplicación.'],
                ],
                'due_at' => now()->addWeek(),
            ],
        );
        app(SyncEvaluationGrades::class)->handle($evaluation);
        EvaluationAttempt::query()->updateOrCreate(
            ['evaluation_id' => $evaluation->id, 'user_id' => $studentUser->id],
            [
                'answers' => [2, 'Sumé el número 4 tres veces.'],
                'correct_count' => 1,
                'score' => 90,
                'manual_scores' => [1 => 80],
                'feedback' => 'Buen procedimiento. Puedes ampliar un poco la explicación.',
                'graded_at' => now(),
            ],
        );
        app(SyncEvaluationGrades::class)->handle($evaluation);

        foreach (
            [
                ['day' => 1, 'code' => 'MAT', 'start' => '07:00', 'end' => '08:00', 'room' => 'Aula 601'],
                ['day' => 1, 'code' => 'LEN', 'start' => '08:00', 'end' => '09:00', 'room' => 'Aula 601'],
                ['day' => 2, 'code' => 'CIE', 'start' => '07:00', 'end' => '08:30', 'room' => 'Laboratorio'],
                ['day' => 3, 'code' => 'ING', 'start' => '09:30', 'end' => '10:30', 'room' => 'Aula 601'],
                ['day' => 4, 'code' => 'MAT', 'start' => '08:00', 'end' => '09:00', 'room' => 'Aula 601'],
                ['day' => 5, 'code' => 'LEN', 'start' => '07:00', 'end' => '08:00', 'room' => 'Biblioteca'],
            ] as $entry
        ) {
            ScheduleEntry::query()->updateOrCreate(
                ['classroom_id' => $classroom->id, 'day_of_week' => $entry['day'], 'starts_at' => $entry['start']],
                ['subject_id' => $subjects[$entry['code']]->id, 'teacher_id' => $teacher->id, 'ends_at' => $entry['end'], 'room' => $entry['room']],
            );
        }

        User::query()->get()->each(function (User $user): void {
            if (! $user->notifications()->where('data->title', 'Bienvenido al centro de notificaciones')->exists()) {
                $user->notify(new SchoolAnnouncement(
                    'Bienvenido al centro de notificaciones',
                    'Aquí recibirás avisos importantes según tu rol dentro de la institución.',
                ));
            }
        });

        if (! $studentUser->notifications()->where('data->title', 'Nuevas actividades publicadas')->exists()) {
            $studentUser->notify(new SchoolAnnouncement(
                'Nuevas actividades publicadas',
                'Tu curso tiene tareas y proyectos próximos. Revisa las fechas de entrega.',
                'tarea',
                '/estudiante/tareas',
            ));
        }
    }
}
