import { Form, Head, Link, useForm } from '@inertiajs/react';
import {
    BookUser,
    ChevronLeft,
    ChevronRight,
    FileSpreadsheet,
    Plus,
    Search,
    Upload,
} from 'lucide-react';
import { useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { enrollments as enrollmentsRoute } from '@/routes/admin';
import { create, importMethod } from '@/routes/admin/enrollments';

type Item = {
    id: number;
    number: string;
    student: string | null;
    document: string;
    email: string | null;
    year: string;
    grade: string;
    campus: string | null;
    shift: string | null;
    status: string;
    enrolled_at: string;
};
type Page = {
    data: Item[];
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export default function EnrollmentIndex({
    enrollments,
    years,
    filters,
}: {
    enrollments: Page;
    years: { id: number; name: string }[];
    filters: { search: string; status: string | null; year: number | null };
}) {
    const [importing, setImporting] = useState(false);
    const importForm = useForm<{ file: File | null }>({ file: null });

    function submitImport() {
        importForm.post(importMethod.url(), {
            forceFormData: true,
            onSuccess: () => {
                setImporting(false);
                importForm.reset();
            },
        });
    }

    return (
        <>
            <Head title="Matrículas" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <BookUser className="size-4" /> Gestión escolar
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Matrículas
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Consulta el grado, año lectivo y estado de cada
                            matrícula.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setImporting(true)}
                        >
                            <Upload /> Importar Excel
                        </Button>
                        <Button
                            asChild
                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                        >
                            <Link href={create()}>
                                <Plus /> Crear matrícula
                            </Link>
                        </Button>
                    </div>
                </div>
                <Form
                    {...enrollmentsRoute.form()}
                    className="bg-card grid gap-3 rounded-2xl border p-4 shadow-sm md:grid-cols-[1fr_180px_180px_auto]"
                >
                    {({ processing }) => (
                        <>
                            <div className="relative">
                                <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search}
                                    placeholder="Nombre, correo o documento"
                                    className="h-10 pl-9"
                                    maxLength={100}
                                    disabled={processing}
                                />
                            </div>
                            <select
                                name="year"
                                defaultValue={filters.year ?? ''}
                                className="bg-background h-10 cursor-pointer rounded-md border px-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500"
                                disabled={processing}
                            >
                                <option value="">Todos los años</option>
                                {years.map((year) => (
                                    <option key={year.id} value={year.id}>
                                        {year.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="status"
                                defaultValue={filters.status ?? ''}
                                className="bg-background h-10 cursor-pointer rounded-md border px-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500"
                                disabled={processing}
                            >
                                <option value="">Todos los estados</option>
                                <option value="activa">Activa</option>
                                <option value="completada">Completada</option>
                                <option value="retirada">Retirada</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                            <Button
                                type="submit"
                                disabled={processing}
                                aria-busy={processing}
                                className="bg-indigo-600 text-white hover:bg-indigo-700"
                            >
                                {processing ? (
                                    <>
                                        <Spinner /> Filtrando…
                                    </>
                                ) : (
                                    <>
                                        <Search /> Filtrar
                                    </>
                                )}
                            </Button>
                        </>
                    )}
                </Form>
                <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="border-b px-5 py-4">
                        <h2 className="font-semibold">
                            Historial de matrículas
                        </h2>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {enrollments.total} registros encontrados
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[850px] text-left text-sm">
                            <thead className="bg-muted/40 text-muted-foreground text-xs">
                                <tr>
                                    {[
                                        'Estudiante',
                                        'Matrícula',
                                        'Año',
                                        'Grado',
                                        'Estado',
                                        'Fecha',
                                    ].map((header) => (
                                        <th
                                            key={header}
                                            className="px-5 py-3 font-medium"
                                        >
                                            {header}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {enrollments.data.map((item) => (
                                    <tr
                                        key={item.id}
                                        className="hover:bg-indigo-500/4"
                                    >
                                        <td className="px-5 py-4">
                                            <p className="font-medium">
                                                {item.student}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {item.document} · {item.email}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4 font-mono text-xs">
                                            {item.number}
                                        </td>
                                        <td className="px-5 py-4">
                                            {item.year}
                                        </td>
                                        <td className="px-5 py-4">
                                            {item.grade}
                                            {item.campus && (
                                                <p className="text-muted-foreground text-xs">
                                                    {item.campus} · {item.shift}
                                                </p>
                                            )}
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge
                                                variant={
                                                    item.status === 'activa'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {item.status}
                                            </Badge>
                                        </td>
                                        <td className="text-muted-foreground px-5 py-4">
                                            {item.enrolled_at}
                                        </td>
                                    </tr>
                                ))}
                                {enrollments.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="text-muted-foreground px-5 py-14 text-center"
                                        >
                                            No encontramos matrículas con estos
                                            filtros.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <Pager page={enrollments} />
                </section>
            </div>
            <Dialog open={importing} onOpenChange={setImporting}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Importar matrículas</DialogTitle>
                        <DialogDescription>
                            Selecciona un archivo Excel o CSV. Revisaremos cada
                            fila y solo importaremos los registros válidos.
                        </DialogDescription>
                    </DialogHeader>
                    <Alert className="border-indigo-200 bg-indigo-50 dark:border-indigo-900 dark:bg-indigo-950/40">
                        <FileSpreadsheet />
                        <AlertTitle>Columnas requeridas</AlertTitle>
                        <AlertDescription>
                            numero_matricula, ano_lectivo, grado, sede, jornada,
                            grupo, tipo_documento, numero_documento,
                            primer_nombre, primer_apellido, fecha_nacimiento,
                            correo y fecha_matricula. Usa fechas AAAA-MM-DD.
                        </AlertDescription>
                    </Alert>
                    <div className="space-y-2">
                        <Input
                            type="file"
                            accept=".xlsx,.csv"
                            disabled={importForm.processing}
                            onChange={(event) =>
                                importForm.setData(
                                    'file',
                                    event.target.files?.[0] ?? null,
                                )
                            }
                            className="cursor-pointer"
                        />
                        {importForm.data.file && (
                            <p className="text-muted-foreground text-xs">
                                Archivo: {importForm.data.file.name}
                            </p>
                        )}
                        {importForm.errors.file && (
                            <p className="text-destructive text-xs">
                                {importForm.errors.file}
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setImporting(false)}
                            disabled={importForm.processing}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={submitImport}
                            disabled={
                                !importForm.data.file || importForm.processing
                            }
                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                        >
                            {importForm.processing ? (
                                <>
                                    <Spinner /> Importando…
                                </>
                            ) : (
                                <>
                                    <Upload /> Confirmar importación
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function Pager({ page }: { page: Page }) {
    return (
        <div className="flex items-center justify-between border-t px-5 py-4">
            <p className="text-muted-foreground text-xs">
                {page.from
                    ? `${page.from}–${page.to} de ${page.total}`
                    : 'Sin resultados'}
            </p>
            <div className="flex gap-2">
                {page.prev_page_url && (
                    <Button asChild size="sm" variant="outline">
                        <Link href={page.prev_page_url}>
                            <ChevronLeft /> Anterior
                        </Link>
                    </Button>
                )}
                {page.next_page_url && (
                    <Button asChild size="sm" variant="outline">
                        <Link href={page.next_page_url}>
                            Siguiente <ChevronRight />
                        </Link>
                    </Button>
                )}
            </div>
        </div>
    );
}

EnrollmentIndex.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Matrículas', href: enrollmentsRoute() },
    ],
};
