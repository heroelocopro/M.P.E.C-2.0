import { Head } from '@inertiajs/react';
import { Bell, CalendarCheck2, GraduationCap, HeartPulse } from 'lucide-react';
import { dashboard } from '@/routes/guardian';

type Student = {
    id: number;
    name: string;
    email: string;
    document: string;
    relationship: string | null;
    course: string | null;
    grades: {
        current_period: string | null;
        current_average: number | null;
        year_average: number | null;
    };
    attendance: {
        total: number;
        present: number;
        late: number;
        absent: number;
    };
    reports: {
        id: number;
        category: string;
        title: string;
        details: string;
        author: string;
        reported_at: string;
    }[];
};

export default function GuardianDashboard({
    students,
}: {
    students: Student[];
}) {
    return (
        <>
            <Head title="Mis estudiantes" />
            <main className="flex flex-col gap-6 p-4 sm:p-8">
                <div>
                    <p className="text-sm font-medium text-indigo-600">
                        Espacio familiar
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold">
                        Mis estudiantes
                    </h1>
                    <p className="text-muted-foreground mt-2">
                        Consulta información escolar, asistencia y novedades de
                        tus hijos o estudiantes a cargo.
                    </p>
                </div>
                {students.map((student) => (
                    <section
                        key={student.id}
                        className="bg-card overflow-hidden rounded-2xl border shadow-sm"
                    >
                        <div className="border-b p-5">
                            <div className="flex items-center gap-3">
                                <span className="rounded-xl bg-indigo-500/10 p-3 text-indigo-600">
                                    <GraduationCap />
                                </span>
                                <div>
                                    <h2 className="text-xl font-semibold">
                                        {student.name}
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {student.course ?? 'Sin curso activo'} ·{' '}
                                        {student.relationship ?? 'Acudiente'} ·{' '}
                                        {student.document}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div className="grid gap-3 border-b p-5 sm:grid-cols-2">
                            <div className="rounded-xl bg-indigo-500/10 p-4">
                                <p className="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                                    Promedio del periodo actual
                                </p>
                                <p className="mt-1 text-3xl font-semibold">
                                    {student.grades.current_average?.toFixed(
                                        2,
                                    ) ?? '—'}
                                </p>
                                <p className="text-muted-foreground mt-1 text-xs">
                                    {student.grades.current_period ??
                                        'Sin periodo académico activo'}
                                </p>
                            </div>
                            <div className="rounded-xl bg-emerald-500/10 p-4">
                                <p className="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                                    Promedio general del año
                                </p>
                                <p className="mt-1 text-3xl font-semibold">
                                    {student.grades.year_average?.toFixed(2) ??
                                        '—'}
                                </p>
                                <p className="text-muted-foreground mt-1 text-xs">
                                    Periodos calificados del año académico
                                    actual
                                </p>
                            </div>
                        </div>
                        <div className="grid gap-3 p-5 sm:grid-cols-4">
                            {[
                                ['Registros', student.attendance.total],
                                ['Presentes', student.attendance.present],
                                ['Tardanzas', student.attendance.late],
                                [
                                    'Ausencias/excusas',
                                    student.attendance.absent,
                                ],
                            ].map(([label, value]) => (
                                <div
                                    key={label}
                                    className="bg-muted/60 rounded-xl p-4"
                                >
                                    <p className="text-muted-foreground text-xs">
                                        {label}
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold">
                                        {value}
                                    </p>
                                </div>
                            ))}
                        </div>
                        <div className="border-t p-5">
                            <h3 className="flex items-center gap-2 font-semibold">
                                <HeartPulse className="size-5 text-rose-500" />{' '}
                                Reportes y novedades
                            </h3>
                            <div className="mt-4 flex flex-col gap-3">
                                {student.reports.map((report) => (
                                    <article
                                        key={report.id}
                                        className="rounded-xl border p-4"
                                    >
                                        <div className="flex flex-wrap justify-between gap-2">
                                            <p className="font-medium">
                                                {report.title}
                                            </p>
                                            <span className="text-muted-foreground text-xs">
                                                {new Date(
                                                    report.reported_at,
                                                ).toLocaleString('es-CO')}
                                            </span>
                                        </div>
                                        <p className="text-muted-foreground mt-2 text-sm whitespace-pre-wrap">
                                            {report.details}
                                        </p>
                                        <p className="mt-2 text-xs font-medium">
                                            {report.category} · {report.author}
                                        </p>
                                    </article>
                                ))}
                                {student.reports.length === 0 && (
                                    <p className="text-muted-foreground flex items-center gap-2 text-sm">
                                        <Bell className="size-4" /> No hay
                                        reportes recientes.
                                    </p>
                                )}
                            </div>
                        </div>
                    </section>
                ))}
                {students.length === 0 && (
                    <div className="bg-card rounded-2xl border border-dashed p-12 text-center">
                        <CalendarCheck2 className="mx-auto mb-3" />
                        <p>
                            No tienes estudiantes vinculados. Comunícate con la
                            institución.
                        </p>
                    </div>
                )}
            </main>
        </>
    );
}
GuardianDashboard.layout = {
    breadcrumbs: [{ title: 'Mis estudiantes', href: dashboard() }],
};
