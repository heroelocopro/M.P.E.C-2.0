import { Form, Head, Link } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    GraduationCap,
    Search,
    UserCheck,
    History,
} from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { graduates, students as studentsRoute } from '@/routes/admin';
import { show as studentHistory } from '@/routes/admin/students';

type Item = {
    id: number;
    name: string | null;
    email: string | null;
    document_type: string;
    document_number: string;
    grade: string | null;
    year: string | null;
    enrollments_count: number;
    graduated_at: string | null;
};
type Page = {
    data: Item[];
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export default function StudentIndex({
    students,
    mode,
    filters,
}: {
    students: Page;
    mode: 'active' | 'graduates';
    filters: { search: string };
}) {
    const isGraduates = mode === 'graduates';
    const route = isGraduates ? graduates : studentsRoute;
    const title = isGraduates ? 'Egresados' : 'Estudiantes activos';
    const Icon = isGraduates ? UserCheck : GraduationCap;
    return (
        <>
            <Head title={title} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                        <Icon className="size-4" /> Gestión escolar
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        {title}
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        {isGraduates
                            ? 'Estudiantes que completaron el último grado y conservan su historial.'
                            : 'Estudiantes con ficha activa y su matrícula académica más reciente.'}
                    </p>
                </div>
                <Form
                    {...route.form()}
                    className="bg-card flex gap-3 rounded-2xl border p-4 shadow-sm"
                >
                    {({ processing }) => (
                        <>
                            <div className="relative flex-1">
                                <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search}
                                    placeholder="Buscar por nombre, correo o documento"
                                    className="h-10 pl-9"
                                    maxLength={100}
                                    disabled={processing}
                                />
                            </div>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="bg-indigo-600 text-white hover:bg-indigo-700"
                            >
                                {processing ? (
                                    <>
                                        <Spinner /> Buscando…
                                    </>
                                ) : (
                                    <>
                                        <Search /> Buscar
                                    </>
                                )}
                            </Button>
                        </>
                    )}
                </Form>
                <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="border-b px-5 py-4">
                        <h2 className="font-semibold">{title}</h2>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {students.total} registros encontrados
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[760px] text-left text-sm">
                            <thead className="bg-muted/40 text-muted-foreground text-xs">
                                <tr>
                                    {[
                                        'Estudiante',
                                        'Documento',
                                        isGraduates
                                            ? 'Último grado'
                                            : 'Grado actual',
                                        'Año',
                                        'Historial',
                                        isGraduates ? 'Egreso' : 'Estado',
                                        'Acciones',
                                    ].map((h) => (
                                        <th
                                            key={h}
                                            className="px-5 py-3 font-medium"
                                        >
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {students.data.map((student) => (
                                    <tr
                                        key={student.id}
                                        className="hover:bg-indigo-500/4"
                                    >
                                        <td className="px-5 py-4">
                                            <div className="flex items-center gap-3">
                                                <Avatar>
                                                    <AvatarFallback className="bg-indigo-500/10 text-xs font-semibold text-indigo-700">
                                                        {student.name
                                                            ?.slice(0, 2)
                                                            .toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="font-medium">
                                                        {student.name}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {student.email}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <p>{student.document_number}</p>
                                            <p className="text-muted-foreground text-xs">
                                                {student.document_type}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4 font-medium">
                                            {student.grade ?? 'Sin matrícula'}
                                        </td>
                                        <td className="px-5 py-4">
                                            {student.year ?? '—'}
                                        </td>
                                        <td className="px-5 py-4">
                                            {student.enrollments_count}{' '}
                                            matrícula(s)
                                        </td>
                                        <td className="text-muted-foreground px-5 py-4">
                                            {isGraduates
                                                ? student.graduated_at
                                                : 'Activo'}
                                        </td>
                                        <td className="px-5 py-4">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={studentHistory(
                                                        student.id,
                                                    )}
                                                >
                                                    <History /> Ver historial
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                                {students.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="text-muted-foreground px-5 py-14 text-center"
                                        >
                                            No encontramos estudiantes en esta
                                            categoría.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex items-center justify-between border-t px-5 py-4">
                        <p className="text-muted-foreground text-xs">
                            {students.from
                                ? `${students.from}–${students.to} de ${students.total}`
                                : 'Sin resultados'}
                        </p>
                        <div className="flex gap-2">
                            {students.prev_page_url && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={students.prev_page_url}>
                                        <ChevronLeft /> Anterior
                                    </Link>
                                </Button>
                            )}
                            {students.next_page_url && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={students.next_page_url}>
                                        Siguiente <ChevronRight />
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

StudentIndex.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Gestión escolar', href: studentsRoute() },
    ],
};
