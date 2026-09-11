import { usePage, Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, BookCheck, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { grades } from '@/routes/teacher';
import { store } from '@/routes/teacher/grades';

type Assignment = { id: number; label: string };
type Period = { id: number; name: string; is_closed: boolean };
type Task = { id: number; title: string; type: string; due_at: string };
type Student = {
    enrollment_id: number;
    name: string;
    document_number: string;
    grades: Record<number, string>;
    final_grade: number | null;
};
type GradebookForm = {
    assignment_id: number | null;
    period_id: number | null;
    grades: Record<number, Record<number, string>>;
};

export default function TeacherGrades({
    assignments,
    periods,
    selected_assignment_id: selectedAssignmentId,
    selected_period_id: selectedPeriodId,
    tasks,
    students,
}: {
    assignments: Assignment[];
    periods: Period[];
    selected_assignment_id: number | null;
    selected_period_id: number | null;
    tasks: Task[];
    students: Student[];
}) {
    const { gradingScale } = usePage().props;
    const selectedPeriod = periods.find(
        (period) => period.id === selectedPeriodId,
    );
    const initialGrades = Object.fromEntries(
        tasks
            .filter((task) => task.type !== 'evaluacion')
            .map((task) => [
                task.id,
                Object.fromEntries(
                    students.map((student) => [
                        student.enrollment_id,
                        student.grades[task.id] ?? '',
                    ]),
                ),
            ]),
    );
    const form = useForm<GradebookForm>({
        assignment_id: selectedAssignmentId,
        period_id: selectedPeriodId,
        grades: initialGrades,
    });

    const updateScore = (taskId: number, enrollmentId: number, score: string) =>
        form.setData('grades', {
            ...form.data.grades,
            [taskId]: {
                ...form.data.grades[taskId],
                [enrollmentId]: score,
            },
        });

    const calculateAverage = (enrollmentId: number) => {
        const scores = tasks
            .map((task) =>
                task.type === 'evaluacion'
                    ? students.find(
                          (student) => student.enrollment_id === enrollmentId,
                      )?.grades[task.id]
                    : form.data.grades[task.id]?.[enrollmentId],
            )
            .filter((score) => score !== '' && score !== undefined)
            .map(Number)
            .filter(Number.isFinite);

        return scores.length === 0
            ? null
            : scores.reduce((total, score) => total + score, 0) / scores.length;
    };

    const invalidScores = tasks
        .filter((task) => task.type !== 'evaluacion')
        .flatMap((task) =>
            students.filter((student) => {
                const value =
                    form.data.grades[task.id]?.[student.enrollment_id];

                return (
                    value !== '' &&
                    value !== undefined &&
                    (!Number.isFinite(Number(value)) ||
                        Number(value) < gradingScale.minimum ||
                        Number(value) > gradingScale.maximum)
                );
            }),
        );
    const hasInvalidScores = invalidScores.length > 0;

    return (
        <>
            <Head title="Notas" />
            <div className="mx-auto flex w-full max-w-360 flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <BookCheck className="size-4" /> Libro de
                            calificaciones
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Notas
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Cada fila corresponde a un estudiante y cada columna
                            a una actividad. La nota final es el promedio de las
                            actividades calificadas.
                        </p>
                    </div>
                    {tasks.length > 0 && students.length > 0 && (
                        <Button
                            disabled={
                                form.processing ||
                                selectedPeriod?.is_closed === true ||
                                tasks.every(
                                    (task) => task.type === 'evaluacion',
                                )
                            }
                            onClick={() => {
                                if (!hasInvalidScores) {
                                    form.post(store.url());
                                }
                            }}
                            className="bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            {form.processing ? <Spinner /> : <Save />} Guardar
                            libro
                        </Button>
                    )}
                </div>

                <section className="bg-card rounded-2xl border p-4 shadow-sm">
                    <div className="flex flex-col gap-2 text-xs font-medium">
                        Curso y materia
                        <select
                            className="bg-background text-foreground h-11 rounded-lg border px-3 text-sm dark:scheme-dark"
                            value={selectedAssignmentId ?? ''}
                            onChange={(event) =>
                                router.get(
                                    grades.url({
                                        query: {
                                            assignment: Number(
                                                event.target.value,
                                            ),
                                        },
                                    }),
                                    {},
                                    { preserveState: false },
                                )
                            }
                        >
                            <option
                                value=""
                                className="bg-popover text-popover-foreground"
                            >
                                Sin asignaciones
                            </option>
                            {assignments.map((assignment) => (
                                <option
                                    key={assignment.id}
                                    value={assignment.id}
                                    className="bg-popover text-popover-foreground"
                                >
                                    {assignment.label}
                                </option>
                            ))}
                        </select>
                        <label className="mt-4 flex flex-col gap-2 text-xs font-medium">
                            Periodo académico
                            <select
                                className="bg-background text-foreground h-11 rounded-lg border px-3 text-sm dark:scheme-dark"
                                value={selectedPeriodId ?? ''}
                                onChange={(event) =>
                                    router.get(
                                        grades.url({
                                            query: {
                                                assignment:
                                                    selectedAssignmentId ??
                                                    undefined,
                                                period: Number(
                                                    event.target.value,
                                                ),
                                            },
                                        }),
                                        {},
                                        { preserveState: false },
                                    )
                                }
                            >
                                <option value="">Sin periodos</option>
                                {periods.map((period) => (
                                    <option key={period.id} value={period.id}>
                                        {period.name}
                                        {period.is_closed ? ' · Cerrado' : ''}
                                    </option>
                                ))}
                            </select>
                        </label>
                    </div>
                </section>

                {(hasInvalidScores || form.hasErrors) && (
                    <div
                        role="alert"
                        className="flex items-start gap-3 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-700 dark:bg-red-500/15 dark:text-red-300"
                    >
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <div>
                            <p className="font-semibold">
                                No se puede guardar el libro de notas
                            </p>
                            <p className="mt-1 text-xs">
                                Todas las calificaciones deben ser valores entre
                                {gradingScale.minimum} y {gradingScale.maximum}.
                                Revisa las celdas marcadas.
                            </p>
                        </div>
                    </div>
                )}

                {tasks.length > 0 && students.length > 0 ? (
                    <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-max border-collapse text-sm">
                                <thead>
                                    <tr className="bg-muted/70 border-b dark:bg-white/5">
                                        <th className="bg-muted/95 sticky left-0 z-20 min-w-64 border-r px-4 py-3 text-left font-semibold dark:bg-zinc-900">
                                            Estudiante
                                        </th>
                                        {tasks.map((task, index) => (
                                            <th
                                                key={task.id}
                                                className="w-32 min-w-32 border-r px-3 py-3 text-center align-top"
                                            >
                                                <span className="text-muted-foreground block text-[10px] font-semibold tracking-wide uppercase">
                                                    {task.type} {index + 1}
                                                </span>
                                                <span
                                                    className="mt-1 line-clamp-2 block text-xs font-semibold"
                                                    title={task.title}
                                                >
                                                    {task.title}
                                                </span>
                                            </th>
                                        ))}
                                        <th className="sticky right-0 z-20 w-28 min-w-28 bg-indigo-500/10 px-3 py-3 text-center text-indigo-700 dark:bg-indigo-400/15 dark:text-indigo-300">
                                            Nota final
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {students.map((student, index) => {
                                        const average = calculateAverage(
                                            student.enrollment_id,
                                        );

                                        return (
                                            <tr
                                                key={student.enrollment_id}
                                                className="hover:bg-muted/35"
                                            >
                                                <th className="bg-card sticky left-0 z-10 border-r px-4 py-3 text-left font-normal">
                                                    <div className="flex items-center gap-3">
                                                        <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-500/10 text-xs font-semibold text-indigo-700 dark:bg-indigo-400/15 dark:text-indigo-300">
                                                            {index + 1}
                                                        </span>
                                                        <div>
                                                            <p className="font-medium">
                                                                {student.name}
                                                            </p>
                                                            <p className="text-muted-foreground text-xs">
                                                                {
                                                                    student.document_number
                                                                }
                                                            </p>
                                                        </div>
                                                    </div>
                                                </th>
                                                {tasks.map((task) => {
                                                    const hasError = Boolean(
                                                        form.errors.grades?.[
                                                            task.id
                                                        ]?.[
                                                            student
                                                                .enrollment_id
                                                        ],
                                                    );
                                                    const value =
                                                        task.type ===
                                                        'evaluacion'
                                                            ? student.grades[
                                                                  task.id
                                                              ]
                                                            : form.data.grades[
                                                                  task.id
                                                              ]?.[
                                                                  student
                                                                      .enrollment_id
                                                              ];
                                                    const hasClientError =
                                                        value !== '' &&
                                                        value !== undefined &&
                                                        (!Number.isFinite(
                                                            Number(value),
                                                        ) ||
                                                            Number(value) <
                                                                gradingScale.minimum ||
                                                            Number(value) >
                                                                gradingScale.maximum);
                                                    return (
                                                        <td
                                                            key={task.id}
                                                            className="border-r p-2 text-center"
                                                        >
                                                            <input
                                                                disabled={
                                                                    task.type ===
                                                                    'evaluacion'
                                                                }
                                                                aria-label={`${task.title}: ${student.name}`}
                                                                type="number"
                                                                min={
                                                                    gradingScale.minimum
                                                                }
                                                                max={
                                                                    gradingScale.maximum
                                                                }
                                                                step="0.01"
                                                                value={
                                                                    form.data
                                                                        .grades[
                                                                        task.id
                                                                    ]?.[
                                                                        student
                                                                            .enrollment_id
                                                                    ] ?? ''
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    updateScore(
                                                                        task.id,
                                                                        student.enrollment_id,
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                                aria-invalid={
                                                                    hasError ||
                                                                    hasClientError
                                                                }
                                                                className={`bg-background text-foreground mx-auto h-9 w-20 rounded-md border px-2 text-center font-medium focus-visible:ring-2 focus-visible:ring-indigo-500/50 focus-visible:outline-none dark:scheme-dark ${hasError || hasClientError ? 'border-destructive ring-destructive/30 ring-2' : ''}`}
                                                            />
                                                            {hasClientError && (
                                                                <p className="text-destructive mt-1 text-[10px] font-medium">
                                                                    Entre{' '}
                                                                    {
                                                                        gradingScale.minimum
                                                                    }{' '}
                                                                    y{' '}
                                                                    {
                                                                        gradingScale.maximum
                                                                    }
                                                                </p>
                                                            )}
                                                        </td>
                                                    );
                                                })}
                                                <td className="sticky right-0 z-10 bg-indigo-500/5 px-3 py-3 text-center dark:bg-indigo-400/10">
                                                    <span
                                                        className={`inline-flex min-w-14 justify-center rounded-full px-2.5 py-1 font-bold ${average === null ? 'bg-muted text-muted-foreground' : average >= gradingScale.passing ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-red-500/15 text-red-700 dark:text-red-300'}`}
                                                    >
                                                        {average === null
                                                            ? '—'
                                                            : average.toFixed(
                                                                  2,
                                                              )}
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                        <div className="text-muted-foreground flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3 text-xs">
                            <span>
                                {students.length} estudiantes · {tasks.length}{' '}
                                actividades
                            </span>
                            <span>
                                Escala de {gradingScale.minimum} a{' '}
                                {gradingScale.maximum} · Aprobación desde{' '}
                                {gradingScale.passing}
                            </span>
                        </div>
                    </section>
                ) : (
                    <div className="bg-card flex min-h-64 flex-col items-center justify-center rounded-2xl border border-dashed p-8 text-center">
                        <BookCheck className="text-muted-foreground size-9" />
                        <h2 className="mt-4 font-semibold">
                            {assignments.length === 0
                                ? 'No tienes cursos asignados'
                                : tasks.length === 0
                                  ? 'No hay actividades para esta materia'
                                  : 'El curso no tiene estudiantes activos'}
                        </h2>
                    </div>
                )}
            </div>
        </>
    );
}

TeacherGrades.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Notas', href: grades() },
    ],
};
