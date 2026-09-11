import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, ScanLine } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { students } from '@/routes/admin';

type RecordItem = {
    id: number;
    student: string | null;
    time: string;
    method: string;
};
type Scanned = {
    name: string | null;
    document: string;
    course: string | null;
    time: string;
};

export default function InstitutionalAttendance({
    records,
    scanned,
}: {
    records: RecordItem[];
    scanned?: Scanned;
}) {
    return (
        <>
            <Head title="Ingreso institucional" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex items-end justify-between gap-4">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <ScanLine className="size-4" /> Control de acceso
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Ingreso institucional
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Cada lectura del carnet registra la llegada al
                            centro educativo, independiente de la asistencia de
                            clase.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={students()}>
                            <ArrowLeft /> Estudiantes
                        </Link>
                    </Button>
                </div>
                {scanned && (
                    <section className="flex items-center gap-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-5">
                        <CheckCircle2 className="size-8 text-emerald-500" />
                        <div>
                            <p className="font-semibold">
                                Ingreso registrado: {scanned.name}
                            </p>
                            <p className="text-muted-foreground text-sm">
                                {scanned.document} ·{' '}
                                {scanned.course ?? 'Sin matrícula'} ·{' '}
                                {scanned.time}
                            </p>
                        </div>
                        <Badge className="ml-auto bg-emerald-600 text-white">
                            Presente
                        </Badge>
                    </section>
                )}
                <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="border-b p-5">
                        <h2 className="font-semibold">Ingresos de hoy</h2>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {records.length} estudiantes registrados
                        </p>
                    </div>
                    <div className="divide-y">
                        {records.map((record) => (
                            <div
                                key={record.id}
                                className="flex items-center justify-between px-5 py-4 text-sm"
                            >
                                <span className="font-medium">
                                    {record.student}
                                </span>
                                <span className="text-muted-foreground">
                                    {record.time} ·{' '}
                                    {record.method.toUpperCase()}
                                </span>
                            </div>
                        ))}
                        {records.length === 0 && (
                            <p className="text-muted-foreground p-12 text-center text-sm">
                                Aún no hay ingresos registrados hoy.
                            </p>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

InstitutionalAttendance.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        {
            title: 'Ingreso institucional',
            href: '/admin/asistencia-institucional',
        },
    ],
};
