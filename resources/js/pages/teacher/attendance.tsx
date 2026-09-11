import { Form, Head, router } from '@inertiajs/react';
import { CalendarCheck2, CheckCircle2, Save, UsersRound } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { attendance } from '@/routes/teacher';
import { store } from '@/routes/teacher/attendance';

type Classroom = { id: number; name: string; detail: string };
type Student = {
    enrollment_id: number;
    name: string;
    document_number: string;
    status: string;
    notes: string | null;
};
const statuses = [
    { value: 'presente', label: 'Presente' },
    { value: 'tarde', label: 'Llegó tarde' },
    { value: 'ausente', label: 'Ausente' },
    { value: 'excusa', label: 'Con excusa' },
];
const statusColors: Record<string, string> = {
    presente:
        'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    tarde: 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    ausente: 'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-300',
    excusa: 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300',
};

export default function TeacherAttendance({
    classrooms,
    selected_classroom_id: selectedId,
    date,
    students,
}: {
    classrooms: Classroom[];
    selected_classroom_id: number | null;
    date: string;
    students: Student[];
}) {
    const changeFilter = (classroomId: number, selectedDate: string) =>
        router.get(
            attendance.url({
                query: { classroom: classroomId, date: selectedDate },
            }),
            {},
            { preserveState: true },
        );

    return (
        <>
            <Head title="Asistencia" />
            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                        <CalendarCheck2 className="size-4" /> Seguimiento diario
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Control de asistencia
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Selecciona uno de tus cursos y registra el estado de
                        cada estudiante.
                    </p>
                </div>
                <section className="bg-card rounded-2xl border p-4 shadow-sm">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <label className="flex flex-col gap-2 text-xs font-medium">
                            Curso
                            <select
                                className="bg-background text-foreground h-10 rounded-lg border px-3 text-sm dark:[color-scheme:dark]"
                                value={selectedId ?? ''}
                                onChange={(event) =>
                                    changeFilter(
                                        Number(event.target.value),
                                        date,
                                    )
                                }
                            >
                                <option
                                    value=""
                                    className="bg-popover text-popover-foreground"
                                >
                                    Sin cursos asignados
                                </option>
                                {classrooms.map((classroom) => (
                                    <option
                                        key={classroom.id}
                                        value={classroom.id}
                                        className="bg-popover text-popover-foreground"
                                    >
                                        {classroom.name} · {classroom.detail}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="flex flex-col gap-2 text-xs font-medium">
                            Fecha
                            <input
                                className="bg-background h-10 rounded-lg border px-3 text-sm"
                                type="date"
                                max={new Date().toISOString().slice(0, 10)}
                                value={date}
                                onChange={(event) =>
                                    selectedId &&
                                    changeFilter(selectedId, event.target.value)
                                }
                            />
                        </label>
                    </div>
                </section>
                {selectedId && students.length > 0 ? (
                    <Form
                        action={store.url()}
                        method="post"
                        className="bg-card overflow-hidden rounded-2xl border shadow-sm"
                    >
                        {({ processing, hasErrors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="classroom_id"
                                    value={selectedId}
                                />
                                <input type="hidden" name="date" value={date} />
                                <div className="flex items-center justify-between border-b p-5">
                                    <div>
                                        <h2 className="font-semibold">
                                            Lista del curso
                                        </h2>
                                        <p className="text-muted-foreground text-xs">
                                            {students.length} estudiantes
                                            matriculados
                                        </p>
                                    </div>
                                    <Button
                                        disabled={processing}
                                        className="bg-indigo-600 text-white hover:bg-indigo-700"
                                    >
                                        {processing ? <Spinner /> : <Save />}{' '}
                                        Guardar asistencia
                                    </Button>
                                </div>
                                {hasErrors && (
                                    <div role="alert" className="border-b border-red-500/25 bg-red-500/10 px-5 py-3 text-xs font-medium text-red-700 dark:bg-red-500/15 dark:text-red-300">
                                        No se pudo guardar. Revisa la fecha, los estados y las observaciones.
                                    </div>
                                )}
                                <div className="divide-y">
                                    {students.map((student, index) => (
                                        <AttendanceRow
                                            key={student.enrollment_id}
                                            student={student}
                                            index={index}
                                        />
                                    ))}
                                </div>
                            </>
                        )}
                    </Form>
                ) : (
                    <div className="bg-card flex min-h-64 flex-col items-center justify-center rounded-2xl border border-dashed p-8 text-center">
                        <span className="flex size-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600">
                            {selectedId ? <CheckCircle2 /> : <UsersRound />}
                        </span>
                        <h2 className="mt-4 font-semibold">
                            {selectedId
                                ? 'Este curso no tiene estudiantes activos'
                                : 'No tienes cursos asignados'}
                        </h2>
                    </div>
                )}
            </div>
        </>
    );
}

function AttendanceRow({
    student,
    index,
}: {
    student: Student;
    index: number;
}) {
    const [status, setStatus] = useState(student.status);
    const needsNote = status === 'ausente' || status === 'excusa';

    return (
        <div className="grid items-start gap-3 p-4 sm:grid-cols-[2rem_1fr_15rem]">
            <span className="text-muted-foreground pt-3 text-center text-xs tabular-nums">
                {index + 1}
            </span>
            <div className="min-w-0 pt-2">
                <p className="truncate text-sm font-medium">{student.name}</p>
                <p className="text-muted-foreground text-xs">
                    {student.document_number}
                </p>
            </div>
            <div className="flex flex-col gap-2">
                <select
                    name={`attendance[${student.enrollment_id}]`}
                    value={status}
                    onChange={(event) => setStatus(event.target.value)}
                    className={`h-10 rounded-lg border px-3 text-sm font-medium dark:[color-scheme:dark] ${statusColors[status]}`}
                >
                    {statuses.map((item) => (
                        <option
                            key={item.value}
                            value={item.value}
                            className="bg-popover text-popover-foreground"
                        >
                            {item.label}
                        </option>
                    ))}
                </select>
                {needsNote && (
                    <textarea
                        name={`notes[${student.enrollment_id}]`}
                        defaultValue={student.notes ?? ''}
                        rows={2}
                        maxLength={1000}
                        placeholder={
                            status === 'excusa'
                                ? 'Escribe la excusa o justificación…'
                                : 'Observación sobre la ausencia…'
                        }
                        className="bg-background rounded-lg border p-2 text-xs"
                    />
                )}
            </div>
        </div>
    );
}

TeacherAttendance.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Asistencia', href: attendance() },
    ],
};
