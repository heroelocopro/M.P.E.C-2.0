import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpenCheck,
    CalendarDays,
    GraduationCap,
    HeartPulse,
    ChevronDown,
    Mail,
    QrCode,
    ShieldCheck,
} from 'lucide-react';
import { QRCodeCanvas } from 'qrcode.react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { dashboard } from '@/routes';
import { students } from '@/routes/admin';
import medical from '@/routes/admin/students/medical';

type Enrollment = {
    id: number;
    number: string;
    year: string;
    grade: string;
    campus: string | null;
    shift: string | null;
    status: string;
    enrolled_at: string;
    completed_at: string | null;
    observations: string | null;
    periods: {
        id: number;
        name: string;
        average: number | null;
        subjects: { name: string; score: number }[];
    }[];
};
type Student = {
    id: number;
    name: string | null;
    email: string | null;
    document_type: string;
    document_number: string;
    birth_date: string;
    status: string;
    graduated_at: string | null;
    card_url: string;
    medical: Medical | null;
    institutional_attendances: { date: string; time: string; method: string }[];
    enrollments: Enrollment[];
};
type Medical = {
    blood_type: string | null;
    allergies: string | null;
    medical_conditions: string | null;
    disabilities: string | null;
    health_provider: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
    gender_identity: string | null;
    sexual_orientation: string | null;
    ethnic_identity: string | null;
    indigenous_people: string | null;
    data_consent: boolean;
};

export default function StudentHistory({ student }: { student: Student }) {
    const { gradingScale } = usePage().props;
    const medicalForm = useForm({
        blood_type: student.medical?.blood_type ?? '',
        allergies: student.medical?.allergies ?? '',
        medical_conditions: student.medical?.medical_conditions ?? '',
        disabilities: student.medical?.disabilities ?? '',
        health_provider: student.medical?.health_provider ?? '',
        emergency_contact_name: student.medical?.emergency_contact_name ?? '',
        emergency_contact_phone: student.medical?.emergency_contact_phone ?? '',
        emergency_contact_relationship:
            student.medical?.emergency_contact_relationship ?? '',
        gender_identity: student.medical?.gender_identity ?? '',
        sexual_orientation: student.medical?.sexual_orientation ?? '',
        ethnic_identity: student.medical?.ethnic_identity ?? '',
        indigenous_people: student.medical?.indigenous_people ?? '',
        data_consent: student.medical?.data_consent ?? false,
    });
    const emailForm = useForm({ subject: '', message: '' });
    return (
        <>
            <Head title={`Historial de ${student.name}`} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <GraduationCap className="size-4" /> Historial
                            académico
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            {student.name}
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            {student.document_type} · {student.document_number}{' '}
                            · {student.email}
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={students()}>
                            <ArrowLeft /> Volver a estudiantes
                        </Link>
                    </Button>
                </div>
                <section className="grid gap-4 sm:grid-cols-3">
                    <Summary label="Estado" value={student.status} />
                    <Summary
                        label="Matrículas registradas"
                        value={String(student.enrollments.length)}
                    />
                    <Summary
                        label="Fecha de nacimiento"
                        value={student.birth_date}
                    />
                </section>
                <div className="grid gap-4 xl:grid-cols-[0.8fr_1.2fr]">
                    <section className="relative overflow-hidden rounded-2xl border border-indigo-500/20 bg-linear-to-br from-indigo-700 via-indigo-800 to-slate-950 p-6 text-white shadow-lg">
                        <div className="relative z-10">
                            <div className="flex items-center gap-2 text-xs font-semibold tracking-widest text-indigo-200 uppercase">
                                <QrCode className="size-4" /> Carnet digital
                            </div>
                            <h2 className="mt-4 text-xl font-bold">
                                Ingreso institucional
                            </h2>
                            <p className="mt-2 text-sm leading-6 text-indigo-100">
                                Este código identifica el carnet sin exponer
                                información médica o personal.
                            </p>
                            <div className="mt-5 inline-flex rounded-2xl bg-white p-3">
                                <QRCodeCanvas
                                    value={student.card_url}
                                    size={150}
                                    includeMargin
                                />
                            </div>
                            <p className="mt-4 text-xs text-indigo-200">
                                Escanea el código desde un dispositivo
                                autorizado.
                            </p>
                        </div>
                    </section>
                    <section className="bg-card rounded-2xl border p-5 shadow-sm">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="flex items-center gap-2 font-semibold">
                                    <HeartPulse className="size-5 text-rose-500" />{' '}
                                    Información de bienestar
                                </h2>
                                <p className="text-muted-foreground mt-1 text-xs">
                                    Datos opcionales y sensibles. Solo visibles
                                    para administración autorizada.
                                </p>
                            </div>
                            <ShieldCheck className="size-5 text-emerald-500" />
                        </div>
                        <form
                            className="mt-5 grid gap-4 sm:grid-cols-2"
                            onSubmit={(event) => {
                                event.preventDefault();
                                medicalForm.put(medical.update.url(student.id));
                            }}
                        >
                            <Field
                                label="Tipo de sangre"
                                value={medicalForm.data.blood_type}
                                onChange={(value) =>
                                    medicalForm.setData('blood_type', value)
                                }
                            />
                            <Field
                                label="EPS / proveedor de salud"
                                value={medicalForm.data.health_provider}
                                onChange={(value) =>
                                    medicalForm.setData(
                                        'health_provider',
                                        value,
                                    )
                                }
                            />
                            <Field
                                label="Contacto de emergencia"
                                value={medicalForm.data.emergency_contact_name}
                                onChange={(value) =>
                                    medicalForm.setData(
                                        'emergency_contact_name',
                                        value,
                                    )
                                }
                            />
                            <Field
                                label="Teléfono de emergencia"
                                value={medicalForm.data.emergency_contact_phone}
                                onChange={(value) =>
                                    medicalForm.setData(
                                        'emergency_contact_phone',
                                        value,
                                    )
                                }
                            />
                            <Field
                                label="Parentesco"
                                value={
                                    medicalForm.data
                                        .emergency_contact_relationship
                                }
                                onChange={(value) =>
                                    medicalForm.setData(
                                        'emergency_contact_relationship',
                                        value,
                                    )
                                }
                            />
                            <Field
                                label="Identidad de género"
                                value={medicalForm.data.gender_identity}
                                onChange={(value) =>
                                    medicalForm.setData(
                                        'gender_identity',
                                        value,
                                    )
                                }
                            />
                            <Field
                                label="Orientación sexual"
                                value={medicalForm.data.sexual_orientation}
                                onChange={(value) =>
                                    medicalForm.setData(
                                        'sexual_orientation',
                                        value,
                                    )
                                }
                            />
                            <Field
                                label="Pertenencia étnica"
                                value={medicalForm.data.ethnic_identity}
                                onChange={(value) =>
                                    medicalForm.setData(
                                        'ethnic_identity',
                                        value,
                                    )
                                }
                            />
                            <Field
                                label="Pueblo indígena (si aplica)"
                                value={medicalForm.data.indigenous_people}
                                onChange={(value) =>
                                    medicalForm.setData(
                                        'indigenous_people',
                                        value,
                                    )
                                }
                            />
                            <TextField
                                label="Alergias"
                                value={medicalForm.data.allergies}
                                onChange={(value) =>
                                    medicalForm.setData('allergies', value)
                                }
                            />
                            <TextField
                                label="Condiciones médicas"
                                value={medicalForm.data.medical_conditions}
                                onChange={(value) =>
                                    medicalForm.setData(
                                        'medical_conditions',
                                        value,
                                    )
                                }
                            />
                            <TextField
                                label="Discapacidad o condición física"
                                value={medicalForm.data.disabilities}
                                onChange={(value) =>
                                    medicalForm.setData('disabilities', value)
                                }
                            />
                            <label className="flex items-center gap-2 text-xs sm:col-span-2">
                                <input
                                    type="checkbox"
                                    checked={medicalForm.data.data_consent}
                                    onChange={(event) =>
                                        medicalForm.setData(
                                            'data_consent',
                                            event.target.checked,
                                        )
                                    }
                                />{' '}
                                Datos registrados con autorización del
                                acudiente/estudiante.
                            </label>
                            <Button
                                disabled={medicalForm.processing}
                                className="bg-indigo-600 text-white hover:bg-indigo-700 sm:col-span-2"
                            >
                                Guardar información sensible
                            </Button>
                        </form>
                    </section>
                </div>
                <section className="bg-card rounded-2xl border p-5 shadow-sm">
                    <h2 className="flex items-center gap-2 font-semibold">
                        <Mail className="size-5 text-indigo-500" /> Contactar al
                        estudiante
                    </h2>
                    <p className="text-muted-foreground mt-1 text-xs">
                        Se enviará al correo registrado:{' '}
                        {student.email ?? 'sin correo'}
                    </p>
                    <form
                        className="mt-4 grid gap-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            emailForm.post(
                                `/admin/estudiantes/${student.id}/correo`,
                                { onSuccess: () => emailForm.reset() },
                            );
                        }}
                    >
                        <input
                            className="bg-background rounded-lg border px-3 py-2 text-sm"
                            placeholder="Asunto"
                            value={emailForm.data.subject}
                            onChange={(event) =>
                                emailForm.setData('subject', event.target.value)
                            }
                        />
                        <textarea
                            className="bg-background min-h-24 rounded-lg border p-3 text-sm"
                            placeholder="Mensaje"
                            value={emailForm.data.message}
                            onChange={(event) =>
                                emailForm.setData('message', event.target.value)
                            }
                        />
                        <Button
                            disabled={emailForm.processing || !student.email}
                            className="w-fit bg-indigo-600 text-white hover:bg-indigo-700"
                        >
                            <Mail /> Enviar correo
                        </Button>
                    </form>
                </section>
                <section className="bg-card rounded-2xl border p-5 shadow-sm">
                    <h2 className="font-semibold">
                        Ingresos institucionales recientes
                    </h2>
                    <div className="mt-4 divide-y">
                        {student.institutional_attendances.map((item) => (
                            <div
                                key={`${item.date}-${item.time}`}
                                className="flex justify-between py-3 text-sm"
                            >
                                <span>{item.date}</span>
                                <span className="text-muted-foreground">
                                    {item.time} · {item.method.toUpperCase()}
                                </span>
                            </div>
                        ))}
                    </div>
                </section>
                <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="border-b px-5 py-4">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <BookOpenCheck className="size-4 text-indigo-600" />{' '}
                            Trayectoria académica
                        </h2>
                        <p className="text-muted-foreground mt-1 text-xs">
                            Cada año permanece disponible aunque el estudiante
                            cambie de grado.
                        </p>
                    </div>
                    <div className="divide-y">
                        {student.enrollments.map((item) => (
                            <Collapsible
                                key={item.id}
                                className="border-b last:border-b-0"
                            >
                                <CollapsibleTrigger asChild>
                                    <button className="group grid w-full gap-3 px-5 py-5 text-left transition hover:bg-indigo-500/5 md:grid-cols-[100px_1fr_auto_auto] md:items-center">
                                        <div className="flex items-center gap-2 font-semibold text-indigo-600">
                                            <CalendarDays className="size-4" />{' '}
                                            {item.year}
                                        </div>
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="font-semibold">
                                                    {item.grade}
                                                </h3>
                                                <Badge
                                                    variant={
                                                        item.status === 'activa'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {item.status}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                {item.campus &&
                                                    `${item.campus} · ${item.shift} · `}
                                                Matrícula {item.number}
                                            </p>
                                        </div>
                                        <div className="text-muted-foreground text-xs">
                                            {item.completed_at
                                                ? `Finalizó ${item.completed_at}`
                                                : 'En curso'}
                                        </div>
                                        <ChevronDown className="text-muted-foreground size-5 justify-self-end transition-transform group-data-[state=open]:rotate-180" />
                                    </button>
                                </CollapsibleTrigger>
                                <CollapsibleContent className="px-5 pb-5">
                                    <div className="bg-muted/20 grid gap-4 rounded-xl p-4">
                                        <div className="text-muted-foreground text-xs">
                                            Ingreso: {item.enrolled_at}
                                            {item.observations &&
                                                ` · ${item.observations}`}
                                        </div>
                                        <div>
                                            <p className="mb-3 text-xs font-semibold tracking-wide text-indigo-600 uppercase dark:text-indigo-400">
                                                Notas por periodo
                                            </p>
                                            <div className="grid gap-3 lg:grid-cols-2">
                                                {item.periods.map((period) => (
                                                    <div
                                                        key={period.id}
                                                        className="bg-muted/20 rounded-xl border p-4"
                                                    >
                                                        <div className="flex items-center justify-between gap-3">
                                                            <p className="text-sm font-semibold">
                                                                {period.name}
                                                            </p>
                                                            <span
                                                                className={`rounded-full px-2.5 py-1 text-xs font-bold ${period.average !== null && period.average >= gradingScale.passing ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground'}`}
                                                            >
                                                                Promedio:{' '}
                                                                {period.average?.toFixed(
                                                                    2,
                                                                ) ?? '—'}
                                                            </span>
                                                        </div>
                                                        <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                                            {period.subjects.map(
                                                                (subject) => (
                                                                    <div
                                                                        key={
                                                                            subject.name
                                                                        }
                                                                        className="flex items-center justify-between gap-2 text-xs"
                                                                    >
                                                                        <span className="text-muted-foreground truncate">
                                                                            {
                                                                                subject.name
                                                                            }
                                                                        </span>
                                                                        <span className="font-bold">
                                                                            {subject.score.toFixed(
                                                                                2,
                                                                            )}
                                                                        </span>
                                                                    </div>
                                                                ),
                                                            )}
                                                        </div>
                                                        {period.subjects
                                                            .length === 0 && (
                                                            <p className="text-muted-foreground mt-2 text-xs">
                                                                Sin notas
                                                                registradas.
                                                            </p>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        ))}
                        {student.enrollments.length === 0 && (
                            <p className="text-muted-foreground px-5 py-14 text-center">
                                Este estudiante todavía no tiene matrículas
                                registradas.
                            </p>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

function Summary({ label, value }: { label: string; value: string }) {
    return (
        <div className="bg-card rounded-2xl border p-5 shadow-sm">
            <p className="text-muted-foreground text-xs">{label}</p>
            <p className="mt-2 font-semibold capitalize">{value}</p>
        </div>
    );
}

function Field({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <label className="flex flex-col gap-2 text-xs font-medium">
            {label}
            <input
                className="bg-background rounded-lg border px-3 py-2 text-sm font-normal"
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
        </label>
    );
}

function TextField({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <label className="flex flex-col gap-2 text-xs font-medium sm:col-span-2">
            {label}
            <textarea
                className="bg-background min-h-16 rounded-lg border p-3 text-sm font-normal"
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
        </label>
    );
}

StudentHistory.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Estudiantes', href: students() },
    ],
};
