import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    GraduationCap,
    LockKeyhole,
    Pencil,
    Plus,
    School,
    CalendarRange,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { academicSettings } from '@/routes/admin';
import { destroy, store, update } from '@/routes/admin/academic-settings';
import { update as updateGradingScale } from '@/routes/admin/grading-scale';
import { close as closePeriod } from '@/routes/admin/periods';

type RecordItem = Record<string, string | number | boolean> & {
    id: number;
    name: string;
};
type Type =
    | 'campuses'
    | 'shifts'
    | 'grades'
    | 'subjects'
    | 'classrooms'
    | 'periods';
type Option = { id: number; name: string };
type FieldDefinition = {
    name: string;
    label: string;
    type?: string;
    wide?: boolean;
    options?: Option[];
};
type Props = {
    campuses: RecordItem[];
    shifts: RecordItem[];
    grades: RecordItem[];
    subjects: RecordItem[];
    classrooms: RecordItem[];
    years: Option[];
    periods: RecordItem[];
};

const meta = {
    campuses: {
        title: 'Sedes',
        icon: Building2,
        description: 'Espacios físicos que funcionan de forma independiente.',
    },
    shifts: {
        title: 'Jornadas',
        icon: School,
        description: 'Horarios disponibles para organizar los cursos.',
    },
    grades: {
        title: 'Grados',
        icon: GraduationCap,
        description: 'Niveles que ofrece la institución.',
    },
    subjects: {
        title: 'Asignaturas',
        icon: BookOpen,
        description: 'Materias que pueden asignarse a profesores.',
    },
    classrooms: {
        title: 'Cursos por sede',
        icon: School,
        description: 'Combinación aislada de sede, jornada, grado y año.',
    },
    periods: {
        title: 'Periodos académicos',
        icon: CalendarRange,
        description: 'Cortes del año lectivo para publicar y cerrar notas.',
    },
};

export default function AcademicSettings(props: Props) {
    const [editor, setEditor] = useState<{
        type: Type;
        item?: RecordItem;
    } | null>(null);
    const [deleting, setDeleting] = useState<{
        type: Type;
        item: RecordItem;
    } | null>(null);
    const structureError = (
        usePage().props.errors as Record<string, string> | undefined
    )?.structure;

    return (
        <>
            <Head title="Configuración académica" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                        <School className="size-4" /> Administración
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Estructura académica
                    </h1>
                    <p className="text-muted-foreground mt-2 max-w-3xl text-sm">
                        Configura la separación real entre sedes, jornadas y
                        cursos. Los registros relacionados no podrán eliminarse
                        accidentalmente.
                    </p>
                </div>
                <GradingScaleSettings />
                {structureError && (
                    <Alert variant="destructive">
                        <AlertTitle>No se puede eliminar</AlertTitle>
                        <AlertDescription>{structureError}</AlertDescription>
                    </Alert>
                )}
                <div className="grid gap-4 lg:grid-cols-2">
                    {(Object.keys(meta) as Type[]).map((type) => (
                        <StructureCard
                            key={type}
                            type={type}
                            items={props[type]}
                            onCreate={() => setEditor({ type })}
                            onEdit={(item) => setEditor({ type, item })}
                            onDelete={(item) => setDeleting({ type, item })}
                            onClosePeriod={(item) => {
                                if (
                                    window.confirm(
                                        `¿Cerrar ${item.name}? Las notas se consolidarán y ya no podrán editarse.`,
                                    )
                                ) {
                                    router.post(
                                        closePeriod.url(item.id),
                                        {},
                                        {
                                            preserveScroll: true,
                                        },
                                    );
                                }
                            }}
                        />
                    ))}
                </div>
            </div>
            <StructureDialog
                key={
                    editor
                        ? `${editor.type}-${editor.item?.id ?? 'new'}`
                        : 'closed'
                }
                state={editor}
                props={props}
                onClose={() => setEditor(null)}
            />
            <Dialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            ¿Eliminar {deleting?.item.name}?
                        </DialogTitle>
                        <DialogDescription>
                            Esta acción solo se permitirá si el registro no
                            tiene matrículas, cursos o asignaciones
                            relacionadas.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleting(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() =>
                                deleting &&
                                router.delete(
                                    destroy.url({
                                        type: deleting.type,
                                        id: deleting.item.id,
                                    }),
                                    { onSuccess: () => setDeleting(null) },
                                )
                            }
                        >
                            <Trash2 /> Confirmar eliminación
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function StructureCard({
    type,
    items,
    onCreate,
    onEdit,
    onDelete,
    onClosePeriod,
}: {
    type: Type;
    items: RecordItem[];
    onCreate: () => void;
    onEdit: (item: RecordItem) => void;
    onDelete: (item: RecordItem) => void;
    onClosePeriod: (item: RecordItem) => void;
}) {
    const info = meta[type];
    const Icon = info.icon;
    return (
        <section
            className={`bg-card rounded-2xl border shadow-sm ${type === 'classrooms' ? 'lg:col-span-2' : ''}`}
        >
            <div className="flex items-start justify-between gap-4 border-b p-5">
                <div className="flex gap-3">
                    <span className="flex size-10 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600">
                        <Icon className="size-5" />
                    </span>
                    <div>
                        <h2 className="font-semibold">{info.title}</h2>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {info.description}
                        </p>
                    </div>
                </div>
                <Button
                    size="sm"
                    onClick={onCreate}
                    className="bg-indigo-600 text-white hover:bg-indigo-700"
                >
                    <Plus /> Agregar
                </Button>
            </div>
            <div className="divide-y">
                {items.map((item) => (
                    <div
                        key={item.id}
                        className="flex items-center justify-between gap-3 px-5 py-3"
                    >
                        <div>
                            <p className="text-sm font-medium">{item.name}</p>
                            <p className="text-muted-foreground text-xs">
                                {describe(type, item)}
                            </p>
                        </div>
                        <div className="flex gap-1">
                            {type === 'periods' && !item.is_closed && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => onClosePeriod(item)}
                                >
                                    <LockKeyhole /> Cerrar periodo
                                </Button>
                            )}
                            {type === 'periods' && item.is_closed && (
                                <span className="rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                    Cerrado
                                </span>
                            )}
                            <Button
                                size="icon"
                                variant="ghost"
                                aria-label="Editar"
                                onClick={() => onEdit(item)}
                            >
                                <Pencil />
                            </Button>
                            <Button
                                size="icon"
                                variant="ghost"
                                className="text-destructive"
                                aria-label="Eliminar"
                                onClick={() => onDelete(item)}
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </div>
                ))}
                {items.length === 0 && (
                    <p className="text-muted-foreground p-8 text-center text-sm">
                        Todavía no hay registros.
                    </p>
                )}
            </div>
        </section>
    );
}

function StructureDialog({
    state,
    props,
    onClose,
}: {
    state: { type: Type; item?: RecordItem } | null;
    props: Props;
    onClose: () => void;
}) {
    const form = useForm<Record<string, string | number | boolean>>(
        defaults(state?.type ?? 'campuses', state?.item ?? ({} as RecordItem)),
    );
    if (!state) return null;
    const submit = () =>
        state.item
            ? form.put(update.url({ type: state.type, id: state.item.id }), {
                  onSuccess: onClose,
              })
            : form.post(store.url({ type: state.type }), {
                  onSuccess: onClose,
              });
    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {state.item ? 'Editar' : 'Agregar'}{' '}
                        {meta[state.type].title.toLowerCase()}
                    </DialogTitle>
                    <DialogDescription>
                        Estos datos definirán cómo se separa la operación
                        académica.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 sm:grid-cols-2">
                    {fields(state.type, props).map((field) => (
                        <div
                            key={field.name}
                            className={
                                field.wide
                                    ? 'space-y-2 sm:col-span-2'
                                    : 'space-y-2'
                            }
                        >
                            <Label>{field.label} *</Label>
                            {field.options ? (
                                <select
                                    className="bg-background h-10 w-full rounded-md border px-3 text-sm"
                                    value={String(form.data[field.name] ?? '')}
                                    onChange={(e) =>
                                        form.setData(field.name, e.target.value)
                                    }
                                >
                                    <option value="">Seleccionar</option>
                                    {field.options.map((option) => (
                                        <option
                                            key={option.id}
                                            value={option.id}
                                        >
                                            {option.name}
                                        </option>
                                    ))}
                                </select>
                            ) : (
                                <Input
                                    type={field.type ?? 'text'}
                                    value={String(form.data[field.name] ?? '')}
                                    onChange={(e) =>
                                        form.setData(
                                            field.name,
                                            field.type === 'number'
                                                ? Number(e.target.value)
                                                : e.target.value,
                                        )
                                    }
                                />
                            )}
                            {form.errors[field.name] && (
                                <p className="text-destructive text-xs">
                                    {form.errors[field.name]}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={onClose}
                        disabled={form.processing}
                    >
                        Cancelar
                    </Button>
                    <Button
                        onClick={submit}
                        disabled={form.processing}
                        className="bg-indigo-600 text-white hover:bg-indigo-700"
                    >
                        {form.processing ? (
                            <>
                                <Spinner /> Guardando…
                            </>
                        ) : (
                            'Guardar cambios'
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function defaults(
    type: Type,
    item: RecordItem,
): Record<string, string | number | boolean> {
    const base: Record<string, string | number | boolean> = { ...item };
    if (type === 'campuses')
        return Object.assign(
            { name: '', code: '', address: '', phone: '' },
            base,
        );
    if (type === 'shifts')
        return Object.assign(
            { name: '', starts_at: '06:30', ends_at: '12:00' },
            base,
        );
    if (type === 'grades')
        return Object.assign(
            { name: '', code: '', sort_order: 1, is_final: false },
            base,
        );
    if (type === 'subjects') return Object.assign({ name: '', code: '' }, base);
    if (type === 'periods')
        return Object.assign(
            {
                name: 'Periodo 1',
                number: 1,
                academic_year_id: '',
                starts_at: '',
                ends_at: '',
                is_closed: false,
            },
            base,
        );
    return Object.assign(
        {
            name: 'A',
            campus_id: '',
            school_shift_id: '',
            grade_id: '',
            academic_year_id: '',
            capacity: 35,
        },
        base,
    );
}
function fields(type: Type, props: Props): FieldDefinition[] {
    if (type === 'campuses')
        return [
            { name: 'name', label: 'Nombre' },
            { name: 'code', label: 'Código' },
            { name: 'address', label: 'Dirección', wide: true },
            { name: 'phone', label: 'Teléfono' },
        ];
    if (type === 'shifts')
        return [
            { name: 'name', label: 'Nombre' },
            { name: 'starts_at', label: 'Inicio', type: 'time' },
            { name: 'ends_at', label: 'Fin', type: 'time' },
        ];
    if (type === 'grades')
        return [
            { name: 'name', label: 'Nombre' },
            { name: 'code', label: 'Código' },
            { name: 'sort_order', label: 'Orden', type: 'number' },
        ];
    if (type === 'subjects')
        return [
            { name: 'name', label: 'Nombre' },
            { name: 'code', label: 'Código' },
        ];
    if (type === 'periods')
        return [
            { name: 'name', label: 'Nombre' },
            { name: 'number', label: 'Número', type: 'number' },
            {
                name: 'academic_year_id',
                label: 'Año lectivo',
                options: props.years,
            },
            { name: 'starts_at', label: 'Inicio', type: 'date' },
            { name: 'ends_at', label: 'Cierre', type: 'date' },
        ];
    return [
        { name: 'name', label: 'Grupo' },
        { name: 'campus_id', label: 'Sede', options: props.campuses },
        { name: 'school_shift_id', label: 'Jornada', options: props.shifts },
        { name: 'grade_id', label: 'Grado', options: props.grades },
        {
            name: 'academic_year_id',
            label: 'Año lectivo',
            options: props.years,
        },
        { name: 'capacity', label: 'Capacidad', type: 'number' },
    ];
}
function describe(type: Type, item: RecordItem) {
    if (type === 'classrooms')
        return `${item.campus} · ${item.shift} · ${item.grade} · ${item.year} · ${item.students} estudiantes`;
    if (type === 'shifts') return `${item.starts_at}–${item.ends_at}`;
    if (type === 'periods')
        return `${item.year} · ${item.starts_at}–${item.ends_at}`;
    return item.code ? `Código ${item.code}` : '';
}

AcademicSettings.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Configuración académica', href: academicSettings() },
    ],
};

function GradingScaleSettings() {
    const { gradingScale } = usePage().props;
    const form = useForm({
        minimum: String(gradingScale.minimum),
        maximum: String(gradingScale.maximum),
        passing: String(gradingScale.passing),
    });
    return (
        <form
            className="bg-card flex flex-col gap-4 rounded-2xl border p-5"
            onSubmit={(event) => {
                event.preventDefault();
                form.put(updateGradingScale.url(), { preserveScroll: true });
            }}
        >
            <div>
                <h2 className="font-semibold">Escala de notas del colegio</h2>
                <p className="text-muted-foreground mt-2 text-sm">
                    Se aplica a evaluaciones, tareas y proyectos. Al cambiar los
                    límites, las notas existentes se convierten
                    proporcionalmente a la nueva escala.
                </p>
            </div>
            <div className="grid gap-4 sm:grid-cols-3">
                {(
                    [
                        ['minimum', 'Nota mínima'],
                        ['maximum', 'Nota máxima'],
                        ['passing', 'Nota de aprobación'],
                    ] as const
                ).map(([key, label]) => (
                    <label key={key} className="flex flex-col gap-2 text-sm">
                        {label}
                        <Input
                            required
                            type="number"
                            min={0}
                            max={999999.99}
                            step="0.01"
                            value={form.data[key]}
                            onChange={(event) =>
                                form.setData(key, event.target.value)
                            }
                        />
                        {form.errors[key] && (
                            <span role="alert" className="text-destructive">
                                {form.errors[key]}
                            </span>
                        )}
                    </label>
                ))}
            </div>
            <Button disabled={form.processing} className="self-start">
                Guardar escala de notas
            </Button>
        </form>
    );
}
