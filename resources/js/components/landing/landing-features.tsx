import {
    BellRing,
    BookOpenCheck,
    CalendarCheck2,
    ChartNoAxesCombined,
    ClipboardCheck,
    ShieldCheck,
    UsersRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

type Feature = {
    icon: LucideIcon;
    title: string;
    description: string;
    color: string;
};

const features: Feature[] = [
    {
        icon: UsersRound,
        title: 'Roles y permisos',
        description:
            'Espacios y accesos claros para administradores, docentes, estudiantes y acudientes.',
        color: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
    },
    {
        icon: ChartNoAxesCombined,
        title: 'Notas y rendimiento',
        description:
            'Registra calificaciones y consulta el progreso académico de forma visual y ordenada.',
        color: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    },
    {
        icon: CalendarCheck2,
        title: 'Control de asistencia',
        description:
            'Toma asistencia en segundos y detecta ausencias o llegadas tarde a tiempo.',
        color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
    {
        icon: ClipboardCheck,
        title: 'Tareas y entregas',
        description:
            'Crea actividades, define fechas límite y acompaña cada entrega desde un solo lugar.',
        color: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    },
    {
        icon: BellRing,
        title: 'Comunicación oportuna',
        description:
            'Mantén informada a la comunidad con avisos y novedades importantes.',
        color: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
    },
    {
        icon: BookOpenCheck,
        title: 'Cursos organizados',
        description:
            'Centraliza grupos, materias, horarios y recursos para facilitar el día a día.',
        color: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
    },
];

export default function LandingFeatures() {
    return (
        <section
            id="funciones"
            className="border-border/60 bg-muted/30 border-t px-6 py-20 lg:px-8 lg:py-28"
        >
            <div className="mx-auto max-w-7xl">
                <div
                    id="comunidad"
                    className="grid gap-8 lg:grid-cols-[0.7fr_1.3fr] lg:gap-16"
                >
                    <div>
                        <span className="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                            Todo lo esencial
                        </span>
                        <h2 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Menos procesos.
                            <br />
                            Más tiempo para enseñar.
                        </h2>
                        <p className="text-muted-foreground mt-5 max-w-md leading-7">
                            Herramientas sencillas que conectan el trabajo
                            académico y administrativo sin complicar a tu
                            comunidad.
                        </p>
                        <div className="bg-background mt-8 flex items-center gap-3 rounded-xl border p-4 shadow-sm">
                            <span className="flex size-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                <ShieldCheck className="size-5" />
                            </span>
                            <div>
                                <p className="text-sm font-medium">
                                    Información protegida
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    Cada persona accede solo a lo que necesita.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {features.map(
                            ({ icon: Icon, title, description, color }) => (
                                <article
                                    key={title}
                                    className="group bg-card rounded-2xl border p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-indigo-950/5"
                                >
                                    <span
                                        className={`mb-5 flex size-10 items-center justify-center rounded-xl ${color}`}
                                    >
                                        <Icon className="size-5" />
                                    </span>
                                    <h3 className="font-semibold">{title}</h3>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">
                                        {description}
                                    </p>
                                </article>
                            ),
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
