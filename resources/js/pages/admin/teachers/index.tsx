import { Head, router, useForm } from '@inertiajs/react';
import { BookOpen, Plus, Trash2, UserPlus, UsersRound } from 'lucide-react';
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
import { teachers } from '@/routes/admin';
import { assign, store, unassign } from '@/routes/admin/teachers';

type Assignment = {
    id: number;
    subject: string;
    classroom: string;
    campus: string;
    shift: string;
    grade: string;
};
type Teacher = {
    id: number;
    name: string;
    email: string;
    personal_email: string | null;
    assignments: Assignment[];
};
type Option = { id: number; name?: string; label?: string };

export default function TeachersIndex({
    teachers: teacherList,
    classrooms,
    subjects,
}: {
    teachers: Teacher[];
    classrooms: Option[];
    subjects: Option[];
}) {
    const [creating, setCreating] = useState(false);
    const [assigning, setAssigning] = useState<Teacher | null>(null);
    const [removing, setRemoving] = useState<Assignment | null>(null);
    const teacherForm = useForm({ name: '', email: '' });
    const assignmentForm = useForm({
        teacher_id: '',
        classroom_id: '',
        subject_id: '',
    });
    const openAssignment = (teacher: Teacher) => {
        assignmentForm.setData('teacher_id', String(teacher.id));
        setAssigning(teacher);
    };
    return (
        <>
            <Head title="Profesores" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <UsersRound className="size-4" /> Equipo docente
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Profesores y asignaciones
                        </h1>
                        <p className="text-muted-foreground mt-2 max-w-2xl text-sm">
                            Cada asignación pertenece a un curso, sede y jornada
                            específicos; nunca mezcla estudiantes de otro grupo.
                        </p>
                    </div>
                    <Button
                        onClick={() => setCreating(true)}
                        className="bg-indigo-600 text-white hover:bg-indigo-700"
                    >
                        <UserPlus /> Agregar profesor
                    </Button>
                </div>
                <div className="grid gap-4 lg:grid-cols-2">
                    {teacherList.map((teacher) => (
                        <section
                            key={teacher.id}
                            className="bg-card rounded-2xl border shadow-sm"
                        >
                            <div className="flex items-start justify-between gap-3 border-b p-5">
                                <div>
                                    <h2 className="font-semibold">
                                        {teacher.name}
                                    </h2>
                                    <p className="text-muted-foreground text-xs">
                                        Institucional: {teacher.email}
                                    </p>
                                    {teacher.personal_email && <p className="text-muted-foreground text-xs">Personal: {teacher.personal_email}</p>}
                                </div>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => openAssignment(teacher)}
                                >
                                    <Plus /> Asignar
                                </Button>
                            </div>
                            <div className="divide-y">
                                {teacher.assignments.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between gap-3 p-4"
                                    >
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <BookOpen className="size-4 text-indigo-600" />
                                                <p className="text-sm font-medium">
                                                    {item.subject}
                                                </p>
                                                <Badge variant="outline">
                                                    {item.grade}{' '}
                                                    {item.classroom}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                {item.campus} · Jornada{' '}
                                                {item.shift}
                                            </p>
                                        </div>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            className="text-destructive"
                                            onClick={() => setRemoving(item)}
                                            aria-label="Eliminar asignación"
                                        >
                                            <Trash2 />
                                        </Button>
                                    </div>
                                ))}
                                {teacher.assignments.length === 0 && (
                                    <p className="text-muted-foreground p-8 text-center text-sm">
                                        Sin asignaciones académicas.
                                    </p>
                                )}
                            </div>
                        </section>
                    ))}
                    {teacherList.length === 0 && (
                        <div className="bg-card text-muted-foreground rounded-2xl border p-12 text-center text-sm lg:col-span-2">
                            Todavía no hay profesores registrados.
                        </div>
                    )}
                </div>
            </div>
            <Dialog open={creating} onOpenChange={setCreating}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Agregar profesor</DialogTitle>
                        <DialogDescription>
                            Se creará una cuenta con rol de profesor.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <Field
                            label="Nombre completo"
                            value={teacherForm.data.name}
                            onChange={(value) =>
                                teacherForm.setData('name', value)
                            }
                            error={teacherForm.errors.name}
                        />
                        <Field
                            label="Correo personal"
                            type="email"
                            value={teacherForm.data.email}
                            onChange={(value) =>
                                teacherForm.setData('email', value)
                            }
                            error={teacherForm.errors.email}
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCreating(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            disabled={teacherForm.processing}
                            onClick={() =>
                                teacherForm.post(store.url(), {
                                    onSuccess: () => {
                                        setCreating(false);
                                        teacherForm.reset();
                                    },
                                })
                            }
                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                        >
                            {teacherForm.processing ? (
                                <>
                                    <Spinner /> Creando…
                                </>
                            ) : (
                                'Crear profesor'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog
                open={Boolean(assigning)}
                onOpenChange={(open) => !open && setAssigning(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Asignar a {assigning?.name}</DialogTitle>
                        <DialogDescription>
                            Selecciona la materia y el curso exacto. La sede y
                            jornada vienen incluidas en el curso.
                        </DialogDescription>
                    </DialogHeader>
                    <Select
                        label="Asignatura"
                        value={assignmentForm.data.subject_id}
                        options={subjects}
                        onChange={(value) =>
                            assignmentForm.setData('subject_id', value)
                        }
                    />
                    {assignmentForm.errors.subject_id && (
                        <p className="text-destructive text-xs">{assignmentForm.errors.subject_id}</p>
                    )}
                    <Select
                        label="Curso, sede y jornada"
                        value={assignmentForm.data.classroom_id}
                        options={classrooms}
                        onChange={(value) =>
                            assignmentForm.setData('classroom_id', value)
                        }
                    />
                    {assignmentForm.errors.classroom_id && (
                        <p className="text-destructive text-xs">{assignmentForm.errors.classroom_id}</p>
                    )}
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setAssigning(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            disabled={assignmentForm.processing}
                            onClick={() =>
                                assignmentForm.post(assign.url(), {
                                    onSuccess: () => {
                                        setAssigning(null);
                                        assignmentForm.reset(
                                            'classroom_id',
                                            'subject_id',
                                        );
                                    },
                                })
                            }
                            className="bg-indigo-600 text-white hover:bg-indigo-700"
                        >
                            {assignmentForm.processing ? (
                                <>
                                    <Spinner /> Asignando…
                                </>
                            ) : (
                                'Confirmar asignación'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog
                open={Boolean(removing)}
                onOpenChange={(open) => !open && setRemoving(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>¿Eliminar asignación?</DialogTitle>
                        <DialogDescription>
                            El profesor dejará de estar asociado a{' '}
                            {removing?.subject} en {removing?.campus} ·{' '}
                            {removing?.shift}.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setRemoving(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() =>
                                removing &&
                                router.delete(unassign.url(removing.id), {
                                    onSuccess: () => setRemoving(null),
                                })
                            }
                        >
                            <Trash2 /> Sí, eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function Field({
    label,
    value,
    onChange,
    error,
    type = 'text',
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    type?: string;
}) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            <Input
                type={type}
                value={value}
                onChange={(e) => onChange(e.target.value)}
            />
            {error && <p className="text-destructive text-xs">{error}</p>}
        </div>
    );
}
function Select({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string;
    options: Option[];
    onChange: (value: string) => void;
}) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            <select
                className="bg-background h-10 w-full rounded-md border px-3 text-sm"
                value={value}
                onChange={(e) => onChange(e.target.value)}
            >
                <option value="">Seleccionar</option>
                {options.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.label ?? option.name}
                    </option>
                ))}
            </select>
        </div>
    );
}

TeachersIndex.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Profesores', href: teachers() },
    ],
};
