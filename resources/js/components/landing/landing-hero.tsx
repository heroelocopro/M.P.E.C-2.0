import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    CalendarDays,
    Check,
    CheckCircle2,
    Clock3,
    TrendingUp,
    Users,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import type { User } from '@/types';

const lessons = [
    { subject: 'Matemáticas', time: '08:00', color: 'bg-indigo-500' },
    { subject: 'Ciencias', time: '10:15', color: 'bg-emerald-500' },
    { subject: 'Literatura', time: '12:30', color: 'bg-amber-500' },
];

function ProductPreview() {
    return (
        <div className="relative mx-auto w-full max-w-[38rem]" id="plataforma">
            <div className="absolute -inset-4 -z-10 rounded-[2rem] bg-indigo-500/10 blur-2xl" />
            <div className="overflow-hidden rounded-[1.5rem] border border-white/70 bg-white/90 shadow-2xl shadow-indigo-950/10 backdrop-blur dark:border-white/10 dark:bg-zinc-900/90 dark:shadow-black/30">
                <div className="flex items-center justify-between border-b px-5 py-4">
                    <div>
                        <p className="text-muted-foreground text-xs">
                            Buenos días, profesora
                        </p>
                        <p className="font-semibold">Panel general</p>
                    </div>
                    <div className="flex -space-x-2">
                        {['MA', 'JR', 'LC'].map((initials, index) => (
                            <span
                                key={initials}
                                className={`flex size-8 items-center justify-center rounded-full border-2 border-white text-[10px] font-semibold text-white dark:border-zinc-900 ${['bg-indigo-500', 'bg-emerald-500', 'bg-amber-500'][index]}`}
                            >
                                {initials}
                            </span>
                        ))}
                    </div>
                </div>
                <div className="grid gap-4 p-4 sm:grid-cols-[1.35fr_0.85fr] sm:p-5">
                    <div className="flex flex-col gap-4">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="rounded-xl bg-indigo-600 p-4 text-white">
                                <Users className="mb-5 size-5 opacity-80" />
                                <p className="text-2xl font-semibold">284</p>
                                <p className="text-xs text-indigo-100">
                                    Estudiantes activos
                                </p>
                            </div>
                            <div className="bg-background/70 rounded-xl border p-4">
                                <TrendingUp className="mb-5 size-5 text-emerald-500" />
                                <p className="text-2xl font-semibold">92%</p>
                                <p className="text-muted-foreground text-xs">
                                    Asistencia mensual
                                </p>
                            </div>
                        </div>
                        <div className="bg-background/70 rounded-xl border p-4">
                            <div className="mb-4 flex items-center justify-between">
                                <p className="text-sm font-medium">
                                    Rendimiento del curso
                                </p>
                                <span className="text-xs font-medium text-emerald-600">
                                    +8.2%
                                </span>
                            </div>
                            <div className="flex h-24 items-end gap-2">
                                {[42, 58, 48, 72, 64, 84, 76, 92, 81, 96].map(
                                    (height, index) => (
                                        <div
                                            key={index}
                                            className="flex h-full flex-1 items-end rounded-t-sm bg-indigo-500/15 dark:bg-indigo-400/20"
                                        >
                                            <div
                                                className="w-full rounded-t-sm bg-indigo-500"
                                                style={{ height: `${height}%` }}
                                            />
                                        </div>
                                    ),
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="bg-background/70 rounded-xl border p-4">
                        <div className="mb-4 flex items-center justify-between">
                            <p className="text-sm font-medium">Clases de hoy</p>
                            <CalendarDays className="text-muted-foreground size-4" />
                        </div>
                        <div className="flex flex-col gap-3">
                            {lessons.map((lesson) => (
                                <div
                                    key={lesson.subject}
                                    className="bg-muted/60 flex items-center gap-3 rounded-lg p-3"
                                >
                                    <span
                                        className={`h-8 w-1 rounded-full ${lesson.color}`}
                                    />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-xs font-medium">
                                            {lesson.subject}
                                        </p>
                                        <p className="text-muted-foreground flex items-center gap-1 text-[10px]">
                                            <Clock3 className="size-2.5" />
                                            {lesson.time}
                                        </p>
                                    </div>
                                    <CheckCircle2 className="size-4 text-emerald-500" />
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function LandingHero({ user }: { user: User | null }) {
    return (
        <section className="px-6 pt-14 pb-20 lg:px-8 lg:pt-20 lg:pb-28">
            <div className="mx-auto grid max-w-7xl items-center gap-14 lg:grid-cols-[0.9fr_1.1fr]">
                <div className="max-w-2xl">
                    <div className="bg-background/75 text-muted-foreground mb-6 inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium shadow-sm backdrop-blur">
                        <span className="size-1.5 rounded-full bg-emerald-500" />
                        Todo tu colegio, en un mismo lugar
                    </div>
                    <h1 className="text-4xl leading-[1.08] font-semibold tracking-[-0.04em] text-balance sm:text-5xl lg:text-6xl">
                        Una gestión escolar{' '}
                        <span className="text-indigo-600 dark:text-indigo-400">
                            simple y conectada.
                        </span>
                    </h1>
                    <p className="text-muted-foreground mt-6 max-w-xl text-base leading-7 text-pretty sm:text-lg">
                        Organiza notas, asistencias, tareas y comunicación desde
                        una plataforma pensada para docentes, estudiantes y
                        familias.
                    </p>
                    <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Button
                            asChild
                            size="lg"
                            className="h-11 rounded-full bg-indigo-600 px-6 text-white hover:bg-indigo-700"
                        >
                            <Link href={user ? dashboard() : login()}>
                                {user
                                    ? 'Ir a mi panel'
                                    : 'Conocer la plataforma'}{' '}
                                <ArrowRight />
                            </Link>
                        </Button>
                        <Button
                            asChild
                            size="lg"
                            variant="outline"
                            className="h-11 rounded-full px-6"
                        >
                            <a href="#funciones">
                                <BookOpen /> Ver funcionalidades
                            </a>
                        </Button>
                    </div>
                    <div className="text-muted-foreground mt-7 flex flex-wrap gap-x-6 gap-y-2 text-xs">
                        {[
                            'Fácil de usar',
                            'Información centralizada',
                            'Acceso por roles',
                        ].map((item) => (
                            <span
                                key={item}
                                className="flex items-center gap-1.5"
                            >
                                <Check className="size-3.5 text-emerald-500" />
                                {item}
                            </span>
                        ))}
                    </div>
                </div>
                <ProductPreview />
            </div>
        </section>
    );
}
