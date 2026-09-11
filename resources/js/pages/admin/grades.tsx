import { usePage, Head, router } from '@inertiajs/react';
import {
    BarChart3,
    BookOpen,
    FileSpreadsheet,
    Filter,
    UsersRound,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { grades } from '@/routes/admin';

type Year = { id: number; name: string; is_active: boolean };
type Period = { id: number; name: string; year: string };
type Student = {
    id: number;
    name: string;
    course: string;
    average: number;
    subjects: { name: string; score: number }[];
};

export default function AdminGrades({
    years,
    periods,
    selected_year_id: selectedYearId,
    selected_period_id: selectedPeriodId,
    students,
}: {
    years: Year[];
    periods: Period[];
    selected_year_id: number | null;
    selected_period_id: number | null;
    students: Student[];
}) {
    const { gradingScale } = usePage().props;
    return (
        <>
            <Head title="Notas por periodo" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                        <BarChart3 className="size-4" /> Seguimiento académico
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Notas por periodo
                    </h1>
                    <p className="text-muted-foreground mt-2 max-w-2xl text-sm">
                        Explora el rendimiento consolidado por año lectivo,
                        periodo, curso y asignatura desde un solo tablero.
                    </p>
                </div>
                <section className="relative overflow-hidden rounded-2xl border border-indigo-500/20 bg-linear-to-br from-indigo-600 via-indigo-700 to-slate-900 p-5 text-white shadow-lg shadow-indigo-950/10 dark:from-indigo-500/80 dark:via-indigo-950 dark:to-zinc-950">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xs font-semibold tracking-wide text-indigo-100 uppercase">
                                <Filter className="size-4" /> Filtros de
                                consulta
                            </div>
                            <p className="mt-2 text-sm text-indigo-100/80">
                                Selecciona el ciclo que quieres revisar.
                            </p>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2 lg:min-w-136">
                            <label className="flex flex-col gap-2 text-xs font-medium text-indigo-100">
                                Año lectivo
                                <select
                                    className="h-11 rounded-lg border border-white/15 bg-white/10 px-3 text-sm text-white outline-none dark:scheme-dark"
                                    value={selectedYearId ?? ''}
                                    onChange={(event) =>
                                        router.get(
                                            grades.url({
                                                query: {
                                                    year: Number(
                                                        event.target.value,
                                                    ),
                                                },
                                            }),
                                            {},
                                            { preserveState: false },
                                        )
                                    }
                                >
                                    {years.map((year) => (
                                        <option
                                            key={year.id}
                                            value={year.id}
                                            className="bg-slate-900"
                                        >
                                            {year.name}
                                            {year.is_active ? ' · Actual' : ''}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="flex flex-col gap-2 text-xs font-medium text-indigo-100">
                                Periodo académico
                                <select
                                    className="h-11 rounded-lg border border-white/15 bg-white/10 px-3 text-sm text-white outline-none dark:scheme-dark"
                                    value={selectedPeriodId ?? ''}
                                    onChange={(event) =>
                                        router.get(
                                            grades.url({
                                                query: {
                                                    year:
                                                        selectedYearId ??
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
                                    <option value="" className="bg-slate-900">
                                        Sin periodos
                                    </option>
                                    {periods.map((period) => (
                                        <option
                                            key={period.id}
                                            value={period.id}
                                            className="bg-slate-900"
                                        >
                                            {period.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        </div>
                    </div>
                </section>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="bg-card flex items-center gap-4 rounded-2xl border p-5 shadow-sm">
                        <span className="flex size-11 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-300">
                            <UsersRound className="size-5" />
                        </span>
                        <div>
                            <p className="text-muted-foreground text-xs">
                                Estudiantes revisados
                            </p>
                            <p className="mt-1 text-2xl font-bold">
                                {students.length}
                            </p>
                        </div>
                    </div>
                    <div className="bg-card flex items-center gap-4 rounded-2xl border p-5 shadow-sm">
                        <span className="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-300">
                            <BookOpen className="size-5" />
                        </span>
                        <div>
                            <p className="text-muted-foreground text-xs">
                                Periodo seleccionado
                            </p>
                            <p className="mt-1 text-lg font-bold">
                                {periods.find(
                                    (period) => period.id === selectedPeriodId,
                                )?.name ?? 'Sin selección'}
                            </p>
                        </div>
                    </div>
                </div>
                <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-3xl text-sm">
                            <thead className="bg-muted/60 border-b text-left dark:bg-white/5">
                                <tr>
                                    <th className="px-5 py-4 font-semibold">
                                        Estudiante
                                    </th>
                                    <th className="px-5 py-4 font-semibold">
                                        Curso
                                    </th>
                                    <th className="px-5 py-4 font-semibold">
                                        Asignaturas
                                    </th>
                                    <th className="px-5 py-4 text-right font-semibold">
                                        Promedio
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {students.map((student) => (
                                    <tr
                                        key={student.id}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-5 py-4 font-medium">
                                            {student.name}
                                        </td>
                                        <td className="text-muted-foreground px-5 py-4">
                                            {student.course}
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="flex flex-wrap gap-2">
                                                {student.subjects.map(
                                                    (subject) => (
                                                        <Badge
                                                            key={subject.name}
                                                            variant="secondary"
                                                        >
                                                            {subject.name}:{' '}
                                                            {subject.score.toFixed(
                                                                2,
                                                            )}
                                                        </Badge>
                                                    ),
                                                )}
                                                {student.subjects.length ===
                                                    0 && (
                                                    <span className="text-muted-foreground text-xs">
                                                        Sin calificaciones
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-5 py-4 text-right">
                                            <span
                                                className={`inline-flex min-w-16 justify-center rounded-full px-2.5 py-1 font-bold ${student.average >= gradingScale.passing ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-red-500/15 text-red-700 dark:text-red-300'}`}
                                            >
                                                {student.average.toFixed(2)}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {students.length === 0 && (
                        <div className="text-muted-foreground flex min-h-56 flex-col items-center justify-center gap-3 p-8 text-center text-sm">
                            <FileSpreadsheet className="size-8" />
                            No hay matrículas ni notas para este periodo.
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

AdminGrades.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Notas', href: grades() },
    ],
};
