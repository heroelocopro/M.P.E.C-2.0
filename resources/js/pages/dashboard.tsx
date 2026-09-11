import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Bell,
    BookOpen,
    BookOpenCheck,
    Building2,
    CalendarCheck2,
    ClipboardList,
    Clock3,
    FolderKanban,
    GraduationCap,
    MapPin,
    Settings2,
    Sparkles,
    TrendingUp,
    UserRoundCheck,
    UsersRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { announcements, dashboard } from '@/routes';
import { academicSettings } from '@/routes/admin';
import { attendance, tasks as teacherTasks } from '@/routes/teacher';

type Metric = {
    title: string;
    value: string;
    detail: string;
    icon: LucideIcon;
    color: string;
};
type StudentActivity = {
    id: number;
    type: 'assignment' | 'notice' | 'project';
    title: string;
    detail: string;
    date: string;
    href: string;
};
type StudentDashboardData = {
    student_name: string;
    institution_name: string;
    academic_year: string | null;
    grade: string | null;
    classroom: string | null;
    campus: string | null;
    campus_address: string | null;
    shift: string | null;
    enrollment_number: string | null;
    average: number | null;
    attendance_percentage: number | null;
    pending_assignments: number;
    recent_activity: StudentActivity[];
};
type TeacherDashboardData = {
    course_count: number;
    subject_count: number;
    student_count: number;
    active_task_count: number;
    assignments: {
        id: number;
        subject: string;
        course: string;
        campus: string;
        shift: string;
    }[];
    today_classes: {
        id: number;
        subject: string;
        course: string;
        starts_at: string;
        ends_at: string;
        room: string | null;
    }[];
};
type AdminDashboardData = {
    student_count: number;
    new_student_count: number;
    average: number | null;
    attendance_percentage: number | null;
    teacher_count: number;
    academic_year: string | null;
    period: string | null;
};

function MetricCard({ metric }: { metric: Metric }) {
    const Icon = metric.icon;
    return (
        <article className="bg-card rounded-2xl border p-5 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-muted-foreground text-sm">
                        {metric.title}
                    </p>
                    <p className="mt-2 text-3xl font-semibold tracking-tight">
                        {metric.value}
                    </p>
                </div>
                <span
                    className={`flex size-10 items-center justify-center rounded-xl ${metric.color}`}
                >
                    <Icon className="size-5" />
                </span>
            </div>
            <p className="text-muted-foreground mt-4 text-xs">
                {metric.detail}
            </p>
        </article>
    );
}

function AdminDashboard({ data }: { data: AdminDashboardData }) {
    const { auth } = usePage().props;
    const metrics: Metric[] = [
        {
            title: 'Estudiantes',
            value: String(data.student_count),
            detail: `${data.new_student_count} matriculados este mes`,
            icon: GraduationCap,
            color: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
        },
        {
            title: 'Promedio general',
            value: data.average?.toFixed(2) ?? '—',
            detail: data.period ?? 'Sin periodo activo',
            icon: TrendingUp,
            color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        },
        {
            title: 'Asistencia',
            value:
                data.attendance_percentage === null
                    ? '—'
                    : `${data.attendance_percentage}%`,
            detail: data.period ?? 'Sin registros del periodo',
            icon: CalendarCheck2,
            color: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
        },
        {
            title: 'Docentes',
            value: String(data.teacher_count),
            detail: 'Usuarios docentes registrados',
            icon: UserRoundCheck,
            color: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        },
    ];
    return (
        <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <Badge variant="secondary" className="mb-3">
                        Vista administrativa
                    </Badge>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Buenos días, {auth.user.name.split(' ')[0]}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Este es el resumen general de tu institución.
                    </p>
                </div>
                <Button asChild variant="outline">
                    <Link href={academicSettings()}>
                        <Settings2 /> Configurar institución
                    </Link>
                </Button>
            </div>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {metrics.map((metric) => (
                    <MetricCard key={metric.title} metric={metric} />
                ))}
            </div>
            <section className="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
                <h2 className="font-semibold">Accesos rápidos</h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    Configuraciones frecuentes de la institución
                </p>
                <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {[
                        {
                            title: 'Sistema de notas',
                            description: 'Escala, mínimos y periodos',
                            icon: BookOpenCheck,
                        },
                        {
                            title: 'Sedes y jornadas',
                            description: 'Organización institucional',
                            icon: Building2,
                        },
                        {
                            title: 'Horarios académicos',
                            description: 'Bloques y calendario',
                            icon: CalendarCheck2,
                        },
                    ].map(({ title, description, icon: Icon }) => (
                        <Link
                            key={title}
                            href={academicSettings()}
                            className="group flex items-center gap-4 rounded-xl border p-4 transition hover:border-indigo-500/40 hover:bg-indigo-500/5"
                        >
                            <span className="bg-muted flex size-10 items-center justify-center rounded-lg">
                                <Icon className="size-5" />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block text-sm font-medium">
                                    {title}
                                </span>
                                <span className="text-muted-foreground block truncate text-xs">
                                    {description}
                                </span>
                            </span>
                            <ArrowRight className="text-muted-foreground size-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    ))}
                </div>
            </section>
            <p className="text-muted-foreground text-center text-xs">
                {data.academic_year
                    ? `Información del año académico ${data.academic_year}`
                    : 'Configura un año académico activo para consultar indicadores.'}
            </p>
        </div>
    );
}

function StudentDashboard({ data }: { data: StudentDashboardData }) {
    const course = [data.grade, data.classroom].filter(Boolean).join(' · ');
    const metrics: Metric[] = [
        {
            title: 'Promedio actual',
            value: data.average?.toFixed(1) ?? '—',
            detail:
                data.average === null
                    ? 'Aún no hay notas registradas'
                    : 'Acumulado del periodo',
            icon: TrendingUp,
            color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        },
        {
            title: 'Tareas pendientes',
            value: String(data.pending_assignments),
            detail:
                data.pending_assignments === 0
                    ? 'Estás al día'
                    : 'Revisa tus próximas entregas',
            icon: ClipboardList,
            color: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
        },
        {
            title: 'Asistencia',
            value:
                data.attendance_percentage === null
                    ? '—'
                    : `${data.attendance_percentage}%`,
            detail:
                data.attendance_percentage === null
                    ? 'Sin registros todavía'
                    : 'Periodo actual',
            icon: CalendarCheck2,
            color: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
        },
    ];
    return (
        <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <section className="relative overflow-hidden rounded-3xl bg-indigo-600 p-6 text-white shadow-lg shadow-indigo-600/15 sm:p-8">
                <div className="absolute -top-16 -right-12 size-52 rounded-full bg-white/10" />
                <div className="absolute -right-4 -bottom-24 size-64 rounded-full bg-violet-400/20" />
                <div className="relative max-w-3xl">
                    <Badge className="border-white/20 bg-white/15 text-white">
                        Panel del estudiante
                    </Badge>
                    <h1 className="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl">
                        Hola, {data.student_name}
                    </h1>
                    <p className="mt-2 max-w-xl text-sm leading-6 text-indigo-100">
                        Aquí tienes lo más importante de tu vida académica en un
                        solo lugar.
                    </p>
                    <div className="mt-6 flex flex-wrap gap-2 text-xs">
                        <span className="rounded-full bg-white/12 px-3 py-1.5">
                            {data.institution_name}
                        </span>
                        {course && (
                            <span className="rounded-full bg-white/12 px-3 py-1.5">
                                {course}
                            </span>
                        )}
                        {data.academic_year && (
                            <span className="rounded-full bg-white/12 px-3 py-1.5">
                                Año lectivo {data.academic_year}
                            </span>
                        )}
                    </div>
                </div>
            </section>
            <div className="grid gap-4 md:grid-cols-3">
                {metrics.map((metric) => (
                    <MetricCard key={metric.title} metric={metric} />
                ))}
            </div>
            <div className="grid gap-4 xl:grid-cols-[1.35fr_0.65fr]">
                <section className="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h2 className="font-semibold">
                                Novedades recientes
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Tareas, avisos y proyectos publicados para tu
                                curso
                            </p>
                        </div>
                        <Bell className="text-muted-foreground size-5" />
                    </div>
                    {data.recent_activity.length === 0 ? (
                        <div className="mt-6 flex min-h-52 flex-col items-center justify-center rounded-2xl border border-dashed bg-indigo-500/2.5 p-8 text-center">
                            <span className="flex size-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                <Sparkles className="size-5" />
                            </span>
                            <h3 className="mt-4 text-sm font-medium">
                                Todo tranquilo por ahora
                            </h3>
                            <p className="text-muted-foreground mt-1 max-w-sm text-sm leading-6">
                                Cuando tus profesores publiquen una tarea, un
                                aviso o un proyecto, aparecerá aquí.
                            </p>
                        </div>
                    ) : (
                        <div className="mt-5 flex flex-col gap-3">
                            {data.recent_activity.map((activity) => (
                                <Link
                                    key={activity.id}
                                    href={activity.href}
                                    className="group flex items-center gap-3 rounded-xl border p-3 transition hover:border-indigo-500/40 hover:bg-indigo-500/5"
                                >
                                    <span
                                        className={`flex size-9 shrink-0 items-center justify-center rounded-lg ${activity.type === 'project' ? 'bg-violet-500/10 text-violet-600 dark:text-violet-300' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300'}`}
                                    >
                                        {activity.type === 'project' ? (
                                            <FolderKanban className="size-4" />
                                        ) : (
                                            <ClipboardList className="size-4" />
                                        )}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-sm font-medium">
                                            {activity.title}
                                        </span>
                                        <span className="text-muted-foreground block text-xs">
                                            {activity.detail}
                                        </span>
                                    </span>
                                    <ArrowRight className="text-muted-foreground size-4 transition-transform group-hover:translate-x-1" />
                                </Link>
                            ))}
                        </div>
                    )}
                </section>
                <section className="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <h2 className="font-semibold">Mi matrícula</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Información del curso actual
                            </p>
                        </div>
                        <GraduationCap className="text-muted-foreground size-5" />
                    </div>
                    {data.grade ? (
                        <dl className="mt-6 flex flex-col gap-5 text-sm">
                            <div>
                                <dt className="text-muted-foreground text-xs">
                                    Institución
                                </dt>
                                <dd className="mt-1 font-medium">
                                    {data.institution_name}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">
                                    Grado y curso
                                </dt>
                                <dd className="mt-1 font-medium">{course}</dd>
                            </div>
                            <div className="flex gap-3">
                                <MapPin className="mt-0.5 size-4 shrink-0 text-indigo-600" />
                                <div>
                                    <dt className="text-muted-foreground text-xs">
                                        Sede y jornada
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {[data.campus, data.shift]
                                            .filter(Boolean)
                                            .join(' · ') || 'Por asignar'}
                                    </dd>
                                    {data.campus_address && (
                                        <dd className="text-muted-foreground mt-1 text-xs">
                                            {data.campus_address}
                                        </dd>
                                    )}
                                </div>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">
                                    Número de matrícula
                                </dt>
                                <dd className="mt-1 font-medium tabular-nums">
                                    {data.enrollment_number}
                                </dd>
                            </div>
                        </dl>
                    ) : (
                        <div className="mt-6 rounded-xl border border-dashed p-5 text-center">
                            <p className="text-sm font-medium">
                                No encontramos una matrícula activa
                            </p>
                            <p className="text-muted-foreground mt-1 text-xs">
                                La administración debe asignarte un grado y
                                curso.
                            </p>
                        </div>
                    )}
                </section>
            </div>
            <section className="grid gap-3 sm:grid-cols-3">
                {[
                    {
                        icon: ClipboardList,
                        title: 'Tareas',
                        detail: 'Consulta tus próximas entregas',
                    },
                    {
                        icon: Bell,
                        title: 'Avisos',
                        detail: 'Noticias importantes del curso',
                    },
                    {
                        icon: FolderKanban,
                        title: 'Proyectos',
                        detail: 'Sigue tus trabajos de largo plazo',
                    },
                ].map(({ icon: Icon, title, detail }) => (
                    <div
                        key={title}
                        className="bg-card flex items-center gap-4 rounded-2xl border p-4 shadow-sm"
                    >
                        <span className="bg-muted flex size-10 items-center justify-center rounded-xl">
                            <Icon className="size-5" />
                        </span>
                        <div>
                            <p className="text-sm font-medium">{title}</p>
                            <p className="text-muted-foreground mt-0.5 text-xs">
                                {detail}
                            </p>
                        </div>
                    </div>
                ))}
            </section>
        </div>
    );
}

function TeacherDashboard({ data }: { data: TeacherDashboardData }) {
    const { auth } = usePage().props;
    const metrics: Metric[] = [
        {
            title: 'Cursos asignados',
            value: String(data.course_count),
            detail: 'Grupos bajo tu responsabilidad',
            icon: GraduationCap,
            color: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
        },
        {
            title: 'Materias',
            value: String(data.subject_count),
            detail: 'Asignaturas que orientas',
            icon: BookOpen,
            color: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
        },
        {
            title: 'Estudiantes',
            value: String(data.student_count),
            detail: 'En tus cursos actuales',
            icon: UsersRound,
            color: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
        },
        {
            title: 'Tareas activas',
            value: String(data.active_task_count),
            detail: 'Con fecha de entrega pendiente',
            icon: ClipboardList,
            color: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        },
    ];

    return (
        <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <section className="relative overflow-hidden rounded-3xl bg-slate-900 p-6 text-white shadow-lg sm:p-8 dark:bg-indigo-950">
                <div className="absolute -top-20 -right-10 size-60 rounded-full bg-indigo-500/20" />
                <div className="relative">
                    <Badge className="border-white/20 bg-white/10 text-white">
                        Panel docente
                    </Badge>
                    <h1 className="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl">
                        Buen día, {auth.user.name.split(' ')[0]}
                    </h1>
                    <p className="mt-2 max-w-xl text-sm leading-6 text-slate-300">
                        Consulta tus clases, organiza actividades y registra la
                        asistencia de tus grupos.
                    </p>
                </div>
            </section>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {metrics.map((metric) => (
                    <MetricCard key={metric.title} metric={metric} />
                ))}
            </div>
            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <section className="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <h2 className="font-semibold">Mis asignaciones</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Cursos y materias que tienes a cargo
                            </p>
                        </div>
                        <BookOpenCheck className="text-muted-foreground size-5" />
                    </div>
                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        {data.assignments.map((assignment) => (
                            <article
                                key={assignment.id}
                                className="rounded-xl border p-4"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div>
                                        <p className="text-sm font-semibold">
                                            {assignment.subject}
                                        </p>
                                        <p className="mt-1 text-sm text-indigo-600 dark:text-indigo-400">
                                            {assignment.course}
                                        </p>
                                    </div>
                                    <Badge variant="outline">
                                        {assignment.shift}
                                    </Badge>
                                </div>
                                <p className="text-muted-foreground mt-3 flex items-center gap-2 text-xs">
                                    <MapPin className="size-3.5" />
                                    {assignment.campus}
                                </p>
                            </article>
                        ))}
                        {data.assignments.length === 0 && (
                            <p className="text-muted-foreground py-12 text-center text-sm sm:col-span-2">
                                Todavía no tienes asignaciones académicas.
                            </p>
                        )}
                    </div>
                </section>
                <section className="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <h2 className="font-semibold">Clases de hoy</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Tu agenda para la jornada
                            </p>
                        </div>
                        <Clock3 className="text-muted-foreground size-5" />
                    </div>
                    <div className="mt-5 flex flex-col gap-3">
                        {data.today_classes.map((entry) => (
                            <article
                                key={entry.id}
                                className="flex items-center gap-4 rounded-xl border p-4"
                            >
                                <div className="text-center">
                                    <p className="text-sm font-semibold tabular-nums">
                                        {entry.starts_at}
                                    </p>
                                    <p className="text-muted-foreground text-[10px]">
                                        {entry.ends_at}
                                    </p>
                                </div>
                                <div className="min-w-0 border-l pl-4">
                                    <p className="truncate text-sm font-medium">
                                        {entry.subject}
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {entry.course}
                                        {entry.room ? ` · ${entry.room}` : ''}
                                    </p>
                                </div>
                            </article>
                        ))}
                        {data.today_classes.length === 0 && (
                            <p className="text-muted-foreground py-12 text-center text-sm">
                                No hay clases programadas para hoy.
                            </p>
                        )}
                    </div>
                </section>
            </div>
            <section className="grid gap-3 sm:grid-cols-3">
                {[
                    {
                        href: attendance(),
                        icon: CalendarCheck2,
                        title: 'Asistencia',
                        detail: 'Registra la asistencia por curso',
                    },
                    {
                        href: teacherTasks(),
                        icon: ClipboardList,
                        title: 'Tareas',
                        detail: 'Publica tareas y proyectos',
                    },
                    {
                        href: announcements(),
                        icon: Bell,
                        title: 'Avisos',
                        detail: 'Publica y revisa comunicados',
                    },
                ].map(({ href, icon: Icon, title, detail }) => (
                    <Link
                        key={title}
                        href={href}
                        className="bg-card group flex items-center gap-4 rounded-2xl border p-4 shadow-sm transition hover:border-indigo-500/40 hover:bg-indigo-500/5"
                    >
                        <span className="bg-muted flex size-10 items-center justify-center rounded-xl">
                            <Icon className="size-5" />
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block text-sm font-medium">
                                {title}
                            </span>
                            <span className="text-muted-foreground block text-xs">
                                {detail}
                            </span>
                        </span>
                        <ArrowRight className="text-muted-foreground size-4 transition-transform group-hover:translate-x-1" />
                    </Link>
                ))}
            </section>
        </div>
    );
}

function PendingRoleDashboard() {
    const { auth } = usePage().props;
    return (
        <div className="flex flex-1 items-center justify-center p-6">
            <div className="bg-card max-w-md rounded-2xl border p-8 text-center shadow-sm">
                <span className="mx-auto flex size-12 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600">
                    <GraduationCap />
                </span>
                <h1 className="mt-5 text-xl font-semibold">
                    Hola, {auth.user.name}
                </h1>
                <p className="text-muted-foreground mt-2 text-sm leading-6">
                    Tu panel de {auth.user.role} será el próximo espacio que
                    construiremos.
                </p>
            </div>
        </div>
    );
}

export default function Dashboard({
    adminDashboard,
    studentDashboard,
    teacherDashboard,
}: {
    adminDashboard?: AdminDashboardData;
    studentDashboard?: StudentDashboardData;
    teacherDashboard?: TeacherDashboardData;
}) {
    const { auth } = usePage().props;
    return (
        <>
            <Head
                title={
                    auth.user.role === 'estudiante'
                        ? 'Mi panel'
                        : 'Panel general'
                }
            />
            {auth.user.role === 'admin' && adminDashboard ? (
                <AdminDashboard data={adminDashboard} />
            ) : auth.user.role === 'estudiante' && studentDashboard ? (
                <StudentDashboard data={studentDashboard} />
            ) : auth.user.role === 'profesor' && teacherDashboard ? (
                <TeacherDashboard data={teacherDashboard} />
            ) : (
                <PendingRoleDashboard />
            )}
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Panel general', href: dashboard() }],
};
