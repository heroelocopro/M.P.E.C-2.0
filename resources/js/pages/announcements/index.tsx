import { Head, useForm } from '@inertiajs/react';
import { Building2, Megaphone, Plus, Presentation } from 'lucide-react';
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
import { announcements, dashboard } from '@/routes';
import { store } from '@/routes/announcements';

type Announcement = {
    id: number;
    kind: 'institucional' | 'docente';
    title: string;
    body: string;
    author: string;
    published_at: string;
    classrooms: string[];
};
type Classroom = { id: number; label: string };

export default function AnnouncementIndex({
    announcements: items,
    can_publish: canPublish,
    publisher_role: role,
    classrooms,
}: {
    announcements: Announcement[];
    can_publish: boolean;
    publisher_role: string;
    classrooms: Classroom[];
}) {
    const [creating, setCreating] = useState(false);
    const form = useForm({
        title: '',
        body: '',
        audience: role === 'admin' ? 'institutional' : 'all_classes',
        classroom_id: '',
    });
    const formatDate = (value: string) =>
        new Intl.DateTimeFormat('es-CO', {
            dateStyle: 'long',
            timeStyle: 'short',
        }).format(new Date(value));

    return (
        <>
            <Head title="Avisos" />
            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <Megaphone className="size-4" /> Comunicaciones
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Avisos
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Los comunicados institucionales llegan a todos; los
                            docentes se dirigen únicamente a sus cursos.
                        </p>
                    </div>
                    {canPublish && (
                        <Button
                            onClick={() => setCreating(true)}
                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                        >
                            <Plus /> Publicar aviso
                        </Button>
                    )}
                </div>
                <div className="flex flex-col gap-4">
                    {items.map((item) => (
                        <article
                            key={item.id}
                            className={`bg-card overflow-hidden rounded-2xl border shadow-sm ${item.kind === 'institucional' ? 'border-amber-500/35' : 'border-indigo-500/25'}`}
                        >
                            <div
                                className={`flex items-center gap-3 border-b px-5 py-3 ${item.kind === 'institucional' ? 'bg-amber-500/8' : 'bg-indigo-500/5'}`}
                            >
                                <span
                                    className={`flex size-9 items-center justify-center rounded-xl ${item.kind === 'institucional' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-400' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400'}`}
                                >
                                    {item.kind === 'institucional' ? (
                                        <Building2 className="size-4" />
                                    ) : (
                                        <Presentation className="size-4" />
                                    )}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <Badge variant="outline">
                                        {item.kind === 'institucional'
                                            ? 'Aviso institucional'
                                            : 'Aviso docente'}
                                    </Badge>
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {item.author} ·{' '}
                                        {formatDate(item.published_at)}
                                    </p>
                                </div>
                            </div>
                            <div className="p-5">
                                <h2 className="font-semibold">{item.title}</h2>
                                <p className="text-muted-foreground mt-2 text-sm leading-6 whitespace-pre-line">
                                    {item.body}
                                </p>
                                {item.classrooms.length > 0 && (
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {item.classrooms.map((classroom) => (
                                            <Badge
                                                key={classroom}
                                                variant="secondary"
                                            >
                                                {classroom}
                                            </Badge>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
                {items.length === 0 && (
                    <div className="bg-card rounded-2xl border border-dashed p-12 text-center">
                        <Megaphone className="text-muted-foreground mx-auto size-8" />
                        <h2 className="mt-4 font-semibold">
                            No hay avisos publicados
                        </h2>
                    </div>
                )}
            </div>
            <Dialog open={creating} onOpenChange={setCreating}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Publicar aviso</DialogTitle>
                        <DialogDescription>
                            {role === 'admin'
                                ? 'Este aviso institucional será visible y notificado a toda la comunidad.'
                                : 'El aviso solo será visible para estudiantes de tus cursos seleccionados.'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-4">
                        {role === 'profesor' && (
                            <label className="flex flex-col gap-2">
                                <Label>Destinatarios</Label>
                                <select
                                    className="bg-background h-10 rounded-md border px-3 text-sm"
                                    value={form.data.audience}
                                    onChange={(event) =>
                                        form.setData(
                                            'audience',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="all_classes">
                                        Todos mis cursos
                                    </option>
                                    <option value="classroom">
                                        Un curso específico
                                    </option>
                                </select>
                            </label>
                        )}
                        {role === 'profesor' &&
                            form.data.audience === 'classroom' && (
                                <label className="flex flex-col gap-2">
                                    <Label>Curso</Label>
                                    <select
                                        className="bg-background h-10 rounded-md border px-3 text-sm"
                                        value={form.data.classroom_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'classroom_id',
                                                event.target.value,
                                            )
                                        }
                                    >
                                        <option value="">Seleccionar</option>
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
                                        <span className="text-destructive text-xs">
                                            {form.errors.classroom_id}
                                        </span>
                                    )}
                                </label>
                            )}
                        <label className="flex flex-col gap-2">
                            <Label>Título</Label>
                            <Input
                                value={form.data.title}
                                onChange={(event) =>
                                    form.setData('title', event.target.value)
                                }
                            />
                            {form.errors.title && (
                                <span className="text-destructive text-xs">
                                    {form.errors.title}
                                </span>
                            )}
                        </label>
                        <label className="flex flex-col gap-2">
                            <Label>Mensaje</Label>
                            <textarea
                                className="bg-background min-h-32 rounded-md border p-3 text-sm"
                                value={form.data.body}
                                onChange={(event) =>
                                    form.setData('body', event.target.value)
                                }
                            />
                            {form.errors.body && (
                                <span className="text-destructive text-xs">
                                    {form.errors.body}
                                </span>
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
                                        form.reset(
                                            'title',
                                            'body',
                                            'classroom_id',
                                        );
                                    },
                                })
                            }
                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                        >
                            {form.processing ? <Spinner /> : <Megaphone />}{' '}
                            Publicar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

AnnouncementIndex.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Avisos', href: announcements() },
    ],
};
