import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BookUser, CheckCircle2, Save } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { enrollments as enrollmentsIndex } from '@/routes/admin';
import { create, store } from '@/routes/admin/enrollments';

export default function CreateEnrollment({
    classrooms,
    documentTypes,
    suggestedNumber,
}: {
    classrooms: { id: number; label: string }[];
    documentTypes: { value: string; label: string }[];
    suggestedNumber: string;
}) {
    const [confirming, setConfirming] = useState(false);
    const form = useForm({
        enrollment_number: suggestedNumber,
        classroom_id: '',
        document_type: 'tarjeta_identidad',
        document_number: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        second_last_name: '',
        birth_date: '',
        gender: '',
        phone: '',
        address: '',
        email: '',
        guardian_name: '',
        guardian_email: '',
        guardian_relationship: '',
        enrolled_at: new Date().toISOString().slice(0, 10),
        observations: '',
    });

    function requestConfirmation(event: FormEvent) {
        event.preventDefault();
        setConfirming(true);
    }

    function submit() {
        form.post(store.url(), {
            onSuccess: () => setConfirming(false),
        });
    }

    const field = (
        name: keyof typeof form.data,
        label: string,
        type = 'text',
        required = false,
    ) => (
        <div className="space-y-2">
            <Label htmlFor={name}>
                {label}
                {required && ' *'}
            </Label>
            <Input
                id={name}
                type={type}
                value={form.data[name]}
                onChange={(event) => form.setData(name, event.target.value)}
                disabled={form.processing}
                required={required}
            />
            {form.errors[name] && (
                <p className="text-destructive text-xs">{form.errors[name]}</p>
            )}
        </div>
    );

    return (
        <>
            <Head title="Crear matrícula" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <BookUser className="size-4" /> Gestión escolar
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Nueva matrícula
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Crea la cuenta del estudiante y registra el grado
                            que cursará.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={enrollmentsIndex()}>
                            <ArrowLeft /> Volver
                        </Link>
                    </Button>
                </div>
                <Alert className="border-indigo-200 bg-indigo-50 text-indigo-950 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-100">
                    <CheckCircle2 />
                    <AlertTitle>Registro centralizado</AlertTitle>
                    <AlertDescription>
                        Si el documento ya existe, conservaremos su historial y
                        agregaremos solamente la matrícula del nuevo año.
                    </AlertDescription>
                </Alert>
                {(form.errors as Record<string, string>).enrollment && (
                    <Alert variant="destructive">
                        <AlertTitle>No se pudo crear la matrícula</AlertTitle>
                        <AlertDescription>
                            {(form.errors as Record<string, string>).enrollment}
                        </AlertDescription>
                    </Alert>
                )}
                <form onSubmit={requestConfirmation} className="space-y-6">
                    <section className="bg-card rounded-2xl border p-5 shadow-sm">
                        <h2 className="font-semibold">Datos de matrícula</h2>
                        <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            {field(
                                'enrollment_number',
                                'Número de matrícula',
                                'text',
                                true,
                            )}
                            <div className="space-y-2 md:col-span-2">
                                <Label>Curso, sede y jornada *</Label>
                                <select
                                    className="bg-background h-10 w-full rounded-md border px-3 text-sm"
                                    value={form.data.classroom_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'classroom_id',
                                            event.target.value,
                                        )
                                    }
                                    required
                                >
                                    <option value="">Seleccionar curso</option>
                                    {classrooms.map((classroom) => (
                                        <option
                                            key={classroom.id}
                                            value={classroom.id}
                                        >
                                            {classroom.label}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.classroom_id && (
                                    <p className="text-destructive text-xs">
                                        {form.errors.classroom_id}
                                    </p>
                                )}
                            </div>
                            {field(
                                'enrolled_at',
                                'Fecha de matrícula',
                                'date',
                                true,
                            )}
                        </div>
                    </section>
                    <section className="bg-card rounded-2xl border p-5 shadow-sm">
                        <h2 className="font-semibold">
                            Información del acudiente
                        </h2>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Se creará su acceso y las credenciales se enviarán
                            al correo personal indicado.
                        </p>
                        <div className="mt-5 grid gap-4 md:grid-cols-3">
                            {field(
                                'guardian_name',
                                'Nombre completo',
                                'text',
                                true,
                            )}
                            {field(
                                'guardian_email',
                                'Correo personal',
                                'email',
                                true,
                            )}
                            {field(
                                'guardian_relationship',
                                'Parentesco',
                                'text',
                                true,
                            )}
                        </div>
                    </section>
                    <section className="bg-card rounded-2xl border p-5 shadow-sm">
                        <h2 className="font-semibold">
                            Información del estudiante
                        </h2>
                        <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-2">
                                <Label>Tipo de documento *</Label>
                                <select
                                    className="bg-background h-10 w-full rounded-md border px-3 text-sm"
                                    value={form.data.document_type}
                                    onChange={(e) =>
                                        form.setData(
                                            'document_type',
                                            e.target.value,
                                        )
                                    }
                                >
                                    {documentTypes.map((type) => (
                                        <option
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            {field(
                                'document_number',
                                'Número de documento',
                                'text',
                                true,
                            )}
                            {field(
                                'email',
                                'Correo personal (recibirá las credenciales)',
                                'email',
                                true,
                            )}
                            {field('first_name', 'Primer nombre', 'text', true)}
                            {field('middle_name', 'Segundo nombre')}
                            {field(
                                'last_name',
                                'Primer apellido',
                                'text',
                                true,
                            )}
                            {field('second_last_name', 'Segundo apellido')}
                            {field(
                                'birth_date',
                                'Fecha de nacimiento',
                                'date',
                                true,
                            )}
                            {field('phone', 'Teléfono')}
                            {field('address', 'Dirección')}
                            {field('gender', 'Género')}
                            {field('observations', 'Observaciones')}
                        </div>
                    </section>
                    <div className="flex justify-end">
                        <Button
                            type="submit"
                            size="lg"
                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                            disabled={form.processing}
                        >
                            <Save /> Revisar y crear
                        </Button>
                    </div>
                </form>
            </div>
            <Dialog open={confirming} onOpenChange={setConfirming}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>¿Confirmar nueva matrícula?</DialogTitle>
                        <DialogDescription>
                            Se creará o actualizará la ficha de{' '}
                            {form.data.first_name || 'este estudiante'} y se
                            registrará en el año seleccionado. Podrás consultar
                            este registro en su historial académico.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setConfirming(false)}
                            disabled={form.processing}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={submit}
                            disabled={form.processing}
                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                        >
                            {form.processing ? (
                                <>
                                    <Spinner /> Creando…
                                </>
                            ) : (
                                <>
                                    <CheckCircle2 /> Sí, crear matrícula
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

CreateEnrollment.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Matrículas', href: enrollmentsIndex() },
        { title: 'Crear', href: create() },
    ],
};
