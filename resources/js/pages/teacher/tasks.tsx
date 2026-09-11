import { Head, useForm } from '@inertiajs/react';
import {
    CalendarClock,
    ClipboardList,
    Download,
    FolderKanban,
    Paperclip,
    Plus,
} from 'lucide-react';
import { useState } from 'react';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { tasks } from '@/routes/teacher';
import { store, update } from '@/routes/teacher/tasks';

type Assignment = {
    id: number;
    classroom_id: number;
    subject_id: number;
    label: string;
};
type Period = { id: number; academic_year_id: number; name: string };
type Task = {
    id: number;
    title: string;
    description: string | null;
    type: string;
    subject: string;
    course: string;
    period: string | null;
    submissions: { id: number; student: string; name: string; url: string }[];
    due_at: string;
    attachments: { id: number; name: string; size: number; url: string }[];
};

export default function TeacherTasks({
    assignments,
    periods,
    tasks: taskList,
}: {
    assignments: Assignment[];
    periods: Period[];
    tasks: Task[];
}) {
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState<Task | null>(null);
    const deadline = useForm({ due_at: '' });
    const form = useForm({
        classroom_id: '',
        subject_id: '',
        academic_period_id: '',
        title: '',
        description: '',
        type: 'tarea',
        due_at: '',
        attachments: [] as File[],
    });
    const formatDate = (value: string) =>
        new Intl.DateTimeFormat('es-CO', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(value));
    const selectAssignment = (value: string) => {
        const assignment = assignments.find(
            (item) => item.id === Number(value),
        );
        form.setData((data) => ({
            ...data,
            classroom_id: String(assignment?.classroom_id ?? ''),
            subject_id: String(assignment?.subject_id ?? ''),
        }));
    };

    return (
        <>
            <Head title="Tareas" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <ClipboardList className="size-4" /> Planeación
                            académica
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Tareas y proyectos
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Publica actividades únicamente para los cursos y
                            materias que tienes asignados.
                        </p>
                    </div>
                    <Button
                        disabled={assignments.length === 0}
                        onClick={() => setCreating(true)}
                        className="bg-indigo-600 text-white hover:bg-indigo-700"
                    >
                        <Plus /> Nueva actividad
                    </Button>
                </div>
                <div className="grid gap-4 lg:grid-cols-2">
                    {taskList.map((task) => (
                        <article
                            key={task.id}
                            className="bg-card rounded-2xl border p-5 shadow-sm"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <span
                                    className={`flex size-10 items-center justify-center rounded-xl ${task.type === 'proyecto' ? 'bg-violet-500/10 text-violet-600' : 'bg-indigo-500/10 text-indigo-600'}`}
                                >
                                    {task.type === 'proyecto' ? (
                                        <FolderKanban className="size-5" />
                                    ) : (
                                        <ClipboardList className="size-5" />
                                    )}
                                </span>
                                <Badge variant="secondary">{task.type}</Badge>
                            </div>
                            <p className="mt-4 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                {task.subject} · {task.course}{' '}
                                {task.period && `· ${task.period}`}
                            </p>
                            <h2 className="mt-1 font-semibold">{task.title}</h2>
                            <Button
                                className="mt-3"
                                variant="outline"
                                onClick={() => {
                                    const date = new Date(task.due_at);
                                    const local = new Date(
                                        date.getTime() -
                                            date.getTimezoneOffset() * 60000,
                                    )
                                        .toISOString()
                                        .slice(0, 16);
                                    deadline.clearErrors();
                                    deadline.setData('due_at', local);
                                    setEditing(task);
                                }}
                            >
                                Editar fecha de entrega
                            </Button>
                            {task.description && (
                                <p className="text-muted-foreground mt-2 line-clamp-2 text-sm">
                                    {task.description}
                                </p>
                            )}
                            <p className="text-muted-foreground mt-4 flex items-center gap-2 border-t pt-4 text-xs">
                                <CalendarClock className="size-4" />
                                Entrega: {formatDate(task.due_at)}
                            </p>
                            {task.attachments.length > 0 && (
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {task.attachments.map((attachment) => (
                                        <a
                                            key={attachment.id}
                                            href={attachment.url}
                                            className="bg-muted flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium hover:bg-indigo-500/10"
                                        >
                                            <Download className="size-3.5" />{' '}
                                            {attachment.name}
                                        </a>
                                    ))}
                                </div>
                            )}
                            {task.submissions.length > 0 && (
                                <div className="mt-4 border-t pt-4">
                                    <p className="text-muted-foreground mb-2 text-xs font-medium">
                                        Entregas recibidas (
                                        {task.submissions.length})
                                    </p>
                                    <div className="flex flex-col gap-2">
                                        {task.submissions.map((submission) => (
                                            <a
                                                key={submission.id}
                                                href={submission.url}
                                                className="bg-muted/70 flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-xs hover:bg-indigo-500/10"
                                            >
                                                <span className="min-w-0 truncate font-medium">
                                                    {submission.student}
                                                </span>
                                                <span className="text-muted-foreground shrink-0">
                                                    {submission.name}
                                                </span>
                                            </a>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </article>
                    ))}
                </div>
                {taskList.length === 0 && (
                    <div className="bg-card text-muted-foreground rounded-2xl border border-dashed p-12 text-center text-sm">
                        Todavía no has publicado tareas o proyectos.
                    </div>
                )}
            </div>
            <Dialog
                open={editing !== null}
                onOpenChange={(open) => {
                    if (!open) setEditing(null);
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Editar fecha de entrega</DialogTitle>
                        <DialogDescription>
                            {editing?.title}. Una fecha futura vuelve a
                            habilitar la actividad.
                        </DialogDescription>
                    </DialogHeader>
                    <label className="flex flex-col gap-2 text-sm">
                        Nueva fecha de entrega
                        <Input
                            type="datetime-local"
                            value={deadline.data.due_at}
                            onChange={(event) =>
                                deadline.setData('due_at', event.target.value)
                            }
                        />
                    </label>
                    {deadline.errors.due_at && (
                        <p role="alert" className="text-destructive text-sm">
                            {deadline.errors.due_at}
                        </p>
                    )}
                    <DialogFooter>
                        <Button
                            disabled={deadline.processing}
                            onClick={() => {
                                if (editing) {
                                    deadline.transform((data) => ({
                                        due_at: data.due_at
                                            ? new Date(
                                                  data.due_at,
                                              ).toISOString()
                                            : '',
                                    }));
                                    deadline.patch(update.url(editing.id), {
                                        onSuccess: () => setEditing(null),
                                    });
                                }
                            }}
                        >
                            Guardar fecha
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog open={creating} onOpenChange={setCreating}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nueva actividad</DialogTitle>
                        <DialogDescription>
                            Los estudiantes del curso recibirán una notificación
                            al publicarla.
                        </DialogDescription>
                    </DialogHeader>
                    {form.hasErrors && (
                        <div
                            role="alert"
                            className="rounded-lg border border-red-500/30 bg-red-500/10 p-3 text-xs font-medium text-red-700 dark:bg-red-500/15 dark:text-red-300"
                        >
                            Revisa los campos marcados antes de publicar la
                            actividad.
                        </div>
                    )}
                    <div className="flex flex-col gap-4">
                        <label className="flex flex-col gap-2">
                            <Label>Materia y curso</Label>
                            <select
                                className="bg-background text-foreground h-10 rounded-md border px-3 text-sm dark:scheme-dark"
                                defaultValue=""
                                onChange={(event) =>
                                    selectAssignment(event.target.value)
                                }
                            >
                                <option value="" disabled>
                                    Seleccionar
                                </option>
                                {assignments.map((assignment) => (
                                    <option
                                        key={assignment.id}
                                        value={assignment.id}
                                    >
                                        {assignment.label}
                                    </option>
                                ))}
                            </select>
                            {(form.errors.classroom_id ||
                                form.errors.subject_id) && (
                                <span className="text-destructive text-xs">
                                    Selecciona una materia y un curso válidos.
                                </span>
                            )}
                        </label>
                        <label className="flex flex-col gap-2">
                            <Label>Periodo académico</Label>
                            <select
                                className="bg-background text-foreground h-10 rounded-md border px-3 text-sm dark:scheme-dark"
                                value={form.data.academic_period_id}
                                onChange={(event) =>
                                    form.setData(
                                        'academic_period_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">Seleccionar periodo</option>
                                {periods.map((period) => (
                                    <option key={period.id} value={period.id}>
                                        {period.name}
                                    </option>
                                ))}
                            </select>
                            {form.errors.academic_period_id && (
                                <span className="text-destructive text-xs">
                                    {form.errors.academic_period_id}
                                </span>
                            )}
                        </label>
                        <label className="flex flex-col gap-2">
                            <Label>Tipo</Label>
                            <select
                                className="bg-background h-10 rounded-md border px-3 text-sm"
                                value={form.data.type}
                                onChange={(event) =>
                                    form.setData('type', event.target.value)
                                }
                            >
                                <option value="tarea">Tarea</option>
                                <option value="proyecto">Proyecto</option>
                            </select>
                            {form.errors.type && (
                                <span className="text-destructive text-xs">
                                    {form.errors.type}
                                </span>
                            )}
                        </label>
                        <label className="flex flex-col gap-2">
                            <Label>Título</Label>
                            <Input
                                value={form.data.title}
                                onChange={(event) =>
                                    form.setData('title', event.target.value)
                                }
                            />
                            {form.errors.description && (
                                <span className="text-destructive text-xs">
                                    {form.errors.description}
                                </span>
                            )}
                            {form.errors.title && (
                                <span className="text-destructive text-xs">
                                    {form.errors.title}
                                </span>
                            )}
                        </label>
                        <label className="flex flex-col gap-2">
                            <Label>Descripción</Label>
                            <textarea
                                className="bg-background min-h-24 rounded-md border p-3 text-sm"
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                            />
                        </label>
                        <label className="flex flex-col gap-2">
                            <Label>Fecha de entrega</Label>
                            <Input
                                type="datetime-local"
                                value={form.data.due_at}
                                onChange={(event) =>
                                    form.setData('due_at', event.target.value)
                                }
                            />
                            {form.errors.due_at && (
                                <span className="text-destructive text-xs">
                                    {form.errors.due_at}
                                </span>
                            )}
                        </label>
                        <label className="flex flex-col gap-2">
                            <Label>Archivos adjuntos</Label>
                            <div className="rounded-xl border border-dashed p-4">
                                <Input
                                    type="file"
                                    multiple
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.webp,.zip"
                                    onChange={(event) =>
                                        form.setData(
                                            'attachments',
                                            Array.from(
                                                event.target.files ?? [],
                                            ),
                                        )
                                    }
                                />
                                <p className="text-muted-foreground mt-2 flex items-center gap-2 text-xs">
                                    <Paperclip className="size-3.5" />
                                    Máximo 5 archivos de 10 MB cada uno.
                                </p>
                            </div>
                            {form.errors.attachments && (
                                <span className="text-destructive text-xs">
                                    {form.errors.attachments}
                                </span>
                            )}
                            {form.progress && (
                                <progress
                                    className="h-2 w-full accent-indigo-600"
                                    value={form.progress.percentage}
                                    max="100"
                                />
                            )}
                        </label>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCreating(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            disabled={form.processing}
                            onClick={() =>
                                form.post(store.url(), {
                                    onSuccess: () => {
                                        setCreating(false);
                                        form.reset();
                                    },
                                })
                            }
                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                        >
                            {form.processing ? <Spinner /> : <Plus />} Publicar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

TeacherTasks.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Tareas', href: tasks() },
    ],
};
