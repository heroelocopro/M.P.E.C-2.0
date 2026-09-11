import { Head, router, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import {
    CalendarClock,
    CheckCircle2,
    ClipboardList,
    Download,
    FolderKanban,
    Pencil,
    Upload,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { tasks } from '@/routes/student';
import { submit as submitTask } from '@/routes/student/tasks';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

type Task = {
    id: number;
    title: string;
    description: string | null;
    type: string;
    subject: string;
    teacher: string;
    period: string | null;
    due_at: string;
    is_overdue: boolean;
    attachments: { id: number; name: string; size: number; url: string }[];
    submission: {
        id: number;
        name: string;
        submitted_at: string;
        url: string;
    } | null;
};

export default function StudentTasks({
    tasks: taskList,
    status,
}: {
    tasks: Task[];
    status: string;
}) {
    const form = useForm<{ file: File | null }>({ file: null });
    const [editingTaskId, setEditingTaskId] = useState<number | null>(null);
    const formatDate = (value: string) =>
        new Intl.DateTimeFormat('es-CO', {
            dateStyle: 'long',
            timeStyle: 'short',
        }).format(new Date(value));

    return (
        <>
            <Head title="Mis tareas" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                        <ClipboardList className="size-4" /> Mi trabajo
                        académico
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Tareas y proyectos
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Organiza tus entregas por fecha y revisa las
                        indicaciones de cada profesor.
                    </p>
                </div>
                <label className="flex flex-col gap-2 text-sm font-medium sm:max-w-xs">
                    Estado de las actividades
                    <select
                        className="bg-background h-10 rounded-md border px-3"
                        value={status}
                        onChange={(event) =>
                            router.get(
                                tasks.url({
                                    query: { status: event.target.value },
                                }),
                            )
                        }
                    >
                        <option value="available">Disponibles</option>
                        <option value="expired">Vencidos</option>
                        <option value="all">Todos</option>
                    </select>
                </label>
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
                                <Badge
                                    variant={
                                        task.is_overdue
                                            ? 'destructive'
                                            : 'secondary'
                                    }
                                >
                                    {task.is_overdue ? 'Vencida' : task.type}
                                </Badge>
                            </div>
                            <p className="mt-4 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                {task.subject}
                            </p>
                            <h2 className="mt-1 font-semibold">{task.title}</h2>
                            {task.description && (
                                <p className="text-muted-foreground mt-2 text-sm leading-6">
                                    {task.description}
                                </p>
                            )}
                            <div className="mt-5 flex items-center gap-2 border-t pt-4 text-xs">
                                <CalendarClock className="text-muted-foreground size-4" />
                                <span className="font-medium">
                                    Entrega: {formatDate(task.due_at)}
                                </span>
                                <span className="text-muted-foreground ml-auto">
                                    {task.teacher}
                                </span>
                            </div>
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
                            <div className="mt-5 border-t pt-4">
                                {task.submission &&
                                editingTaskId !== task.id ? (
                                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-emerald-500/10 p-3 text-sm">
                                        <div className="flex min-w-0 items-center gap-2 text-emerald-700 dark:text-emerald-300">
                                            <CheckCircle2 className="size-4 shrink-0" />
                                            <span className="truncate font-medium">
                                                Entrega enviada:{' '}
                                                {task.submission.name}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <a
                                                href={task.submission.url}
                                                className="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-300"
                                            >
                                                Descargar
                                            </a>
                                            <button
                                                type="button"
                                                className="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-300"
                                                onClick={() =>
                                                    setEditingTaskId(task.id)
                                                }
                                            >
                                                <Pencil className="size-3.5" />{' '}
                                                Editar
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    <form
                                        className="flex flex-col gap-3 sm:flex-row sm:items-end"
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            if (!form.data.file) {
                                                toast.error(
                                                    'Selecciona un archivo antes de enviar la entrega.',
                                                );
                                                return;
                                            }
                                            form.post(
                                                submitTask.url({
                                                    academicTask: task.id,
                                                }),
                                                {
                                                    forceFormData: true,
                                                    onSuccess: () => {
                                                        form.reset('file');
                                                        setEditingTaskId(null);
                                                    },
                                                    onError: (errors) =>
                                                        toast.error(
                                                            errors.file ??
                                                                'No fue posible enviar la entrega. Inténtalo de nuevo.',
                                                        ),
                                                },
                                            );
                                        }}
                                    >
                                        <label className="flex min-w-0 flex-1 flex-col gap-2 text-xs font-medium">
                                            Entregar trabajo
                                            <Input
                                                type="file"
                                                accept=".pdf,.doc,.docx,.txt"
                                                onChange={(event) =>
                                                    form.setData(
                                                        'file',
                                                        event.target
                                                            .files?.[0] ?? null,
                                                    )
                                                }
                                            />
                                            <span className="text-muted-foreground text-[11px] font-normal">
                                                PDF, Word o TXT · máximo 10 MB
                                            </span>
                                        </label>
                                        <Button
                                            type="submit"
                                            disabled={
                                                form.processing ||
                                                !form.data.file
                                            }
                                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                                        >
                                            <Upload />{' '}
                                            {form.processing
                                                ? 'Enviando…'
                                                : 'Enviar'}
                                        </Button>
                                        {editingTaskId === task.id && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    form.reset('file');
                                                    setEditingTaskId(null);
                                                }}
                                            >
                                                Cancelar
                                            </Button>
                                        )}
                                    </form>
                                )}
                                {form.errors.file && (
                                    <p className="text-destructive mt-2 text-xs">
                                        {form.errors.file}
                                    </p>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
                {taskList.length === 0 && (
                    <div className="bg-card flex min-h-72 flex-col items-center justify-center rounded-2xl border border-dashed p-8 text-center">
                        <span className="flex size-14 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600">
                            <CheckCircle2 />
                        </span>
                        <h2 className="mt-4 font-semibold">
                            No hay actividades para este filtro
                        </h2>
                        <p className="text-muted-foreground mt-1 max-w-md text-sm">
                            Las tareas y proyectos asignados a tu curso
                            aparecerán aquí.
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}

StudentTasks.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Mis tareas', href: tasks() },
    ],
};
