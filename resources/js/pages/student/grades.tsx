import { usePage, Head, router } from '@inertiajs/react';
import { BookOpenCheck, Download, GraduationCap } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import student from '@/routes/student';

type Period = { id: number; name: string; year: string };
type Report = {
    student: string;
    year: string;
    grade: string;
    average: number;
    subjects: { name: string; score: number; activities: number }[];
};

export default function StudentGrades({
    periods,
    selected_period_id: selectedPeriodId,
    report,
}: {
    periods: Period[];
    selected_period_id: number | null;
    report: Report | null;
}) {
    const { gradingScale } = usePage().props;
    return (
        <>
            <Head title="Mis notas" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                        <GraduationCap className="size-4" /> Mi espacio
                        académico
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Mis notas
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Consulta tus resultados finales y descarga el reporte de
                        cada periodo.
                    </p>
                </div>
                <section className="bg-card flex flex-col gap-4 rounded-2xl border p-4 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                    <label className="flex max-w-sm flex-1 flex-col gap-2 text-xs font-medium">
                        Periodo académico
                        <select
                            className="bg-background text-foreground h-11 rounded-lg border px-3 text-sm dark:scheme-dark"
                            value={selectedPeriodId ?? ''}
                            onChange={(event) =>
                                router.get(
                                    student.grades.url({
                                        query: {
                                            period: Number(event.target.value),
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
                                    {period.name} · {period.year}
                                </option>
                            ))}
                        </select>
                    </label>
                    {report && selectedPeriodId && (
                        <Button
                            asChild
                            className="bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            <a
                                href={student.grades.pdf.url({
                                    period: selectedPeriodId,
                                })}
                            >
                                <Download /> Descargar PDF
                            </a>
                        </Button>
                    )}
                </section>
                {report ? (
                    <>
                        <section className="bg-card grid gap-4 rounded-2xl border p-5 shadow-sm sm:grid-cols-3">
                            <div>
                                <p className="text-muted-foreground text-xs">
                                    Estudiante
                                </p>
                                <p className="mt-1 font-semibold">
                                    {report.student}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">
                                    Curso
                                </p>
                                <p className="mt-1 font-semibold">
                                    {report.grade}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">
                                    Promedio del periodo
                                </p>
                                <p className="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                    {report.average.toFixed(2)}
                                </p>
                            </div>
                        </section>
                        <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                            <div className="border-b p-5">
                                <h2 className="flex items-center gap-2 font-semibold">
                                    <BookOpenCheck className="size-5 text-indigo-500" />{' '}
                                    Resultado por asignatura
                                </h2>
                            </div>
                            <div className="divide-y">
                                {report.subjects.map((subject) => (
                                    <div
                                        key={subject.name}
                                        className="flex items-center justify-between gap-4 px-5 py-4"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {subject.name}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {subject.activities} actividades
                                                calificadas
                                            </p>
                                        </div>
                                        <span
                                            className={`rounded-full px-3 py-1 font-bold ${subject.score >= gradingScale.passing ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-red-500/15 text-red-700 dark:text-red-300'}`}
                                        >
                                            {subject.score.toFixed(2)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </section>
                    </>
                ) : (
                    <div className="bg-card text-muted-foreground flex min-h-64 flex-col items-center justify-center rounded-2xl border border-dashed p-8 text-center text-sm">
                        <BookOpenCheck className="size-9" />
                        <p className="mt-4 font-medium">
                            Todavía no hay un reporte disponible.
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}

StudentGrades.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Mis notas', href: student.grades() },
    ],
};
