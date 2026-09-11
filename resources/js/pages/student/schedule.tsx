import { Head } from '@inertiajs/react';
import {
    BookOpen,
    CalendarDays,
    Clock3,
    MapPin,
    UserRound,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { schedule } from '@/routes/student';

type Entry = {
    id: number;
    day_of_week: number;
    subject: string;
    teacher: string | null;
    starts_at: string;
    ends_at: string;
    room: string | null;
};
const days = [
    { id: 1, name: 'Lunes' },
    { id: 2, name: 'Martes' },
    { id: 3, name: 'Miércoles' },
    { id: 4, name: 'Jueves' },
    { id: 5, name: 'Viernes' },
];

export default function StudentSchedule({
    course,
    campus,
    shift,
    entries,
}: {
    course: string | null;
    campus: string | null;
    shift: string | null;
    entries: Entry[];
}) {
    return (
        <>
            <Head title="Mi horario" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <CalendarDays className="size-4" /> Semana académica
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Mi horario
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Clases organizadas por día, hora y salón.
                        </p>
                    </div>
                    {course && (
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">{course}</Badge>
                            <Badge variant="outline">
                                {campus} · {shift}
                            </Badge>
                        </div>
                    )}
                </div>
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    {days.map((day) => {
                        const dayEntries = entries.filter(
                            (entry) => entry.day_of_week === day.id,
                        );
                        return (
                            <section
                                key={day.id}
                                className="bg-card overflow-hidden rounded-2xl border shadow-sm"
                            >
                                <div className="border-b bg-indigo-500/5 p-4">
                                    <h2 className="font-semibold">
                                        {day.name}
                                    </h2>
                                    <p className="text-muted-foreground text-xs">
                                        {dayEntries.length}{' '}
                                        {dayEntries.length === 1
                                            ? 'clase'
                                            : 'clases'}
                                    </p>
                                </div>
                                <div className="flex flex-col gap-3 p-3">
                                    {dayEntries.map((entry) => (
                                        <article
                                            key={entry.id}
                                            className="rounded-xl border p-3"
                                        >
                                            <div className="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                                <Clock3 className="size-3.5" />
                                                {entry.starts_at} –{' '}
                                                {entry.ends_at}
                                            </div>
                                            <div className="mt-3 flex gap-2">
                                                <BookOpen className="text-muted-foreground mt-0.5 size-4 shrink-0" />
                                                <p className="text-sm font-medium">
                                                    {entry.subject}
                                                </p>
                                            </div>
                                            {entry.teacher && (
                                                <p className="text-muted-foreground mt-2 flex items-center gap-2 text-xs">
                                                    <UserRound className="size-3.5" />
                                                    {entry.teacher}
                                                </p>
                                            )}
                                            {entry.room && (
                                                <p className="text-muted-foreground mt-1 flex items-center gap-2 text-xs">
                                                    <MapPin className="size-3.5" />
                                                    {entry.room}
                                                </p>
                                            )}
                                        </article>
                                    ))}
                                    {dayEntries.length === 0 && (
                                        <p className="text-muted-foreground py-8 text-center text-xs">
                                            Sin clases
                                        </p>
                                    )}
                                </div>
                            </section>
                        );
                    })}
                </div>
                {!course && (
                    <div className="bg-card rounded-2xl border border-dashed p-10 text-center">
                        <h2 className="font-semibold">
                            Aún no tienes un curso asignado
                        </h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Tu horario estará disponible cuando se complete tu
                            matrícula.
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}

StudentSchedule.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Mi horario', href: schedule() },
    ],
};
