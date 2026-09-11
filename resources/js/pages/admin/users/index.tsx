import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    BookOpen,
    ChevronLeft,
    ChevronRight,
    GraduationCap,
    HeartHandshake,
    KeyRound,
    Search,
    ShieldCheck,
    X,
    UserRoundCheck,
    UsersRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { users as usersRoute } from '@/routes/admin';
import type { UserRole } from '@/types';

type UserItem = {
    id: number;
    name: string;
    email: string;
    personal_email: string | null;
    role: UserRole;
    role_label: string;
    is_verified: boolean;
    created_at: string | null;
};
type Paginator = {
    data: UserItem[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
type Counts = {
    all: number;
    administrators: number;
    teachers: number;
    students: number;
    guardians: number;
};
type Filter = {
    label: string;
    role: UserRole | null;
    count: keyof Counts;
    icon: LucideIcon;
};

const filters: Filter[] = [
    { label: 'Todos', role: null, count: 'all', icon: UsersRound },
    {
        label: 'Administradores',
        role: 'admin',
        count: 'administrators',
        icon: ShieldCheck,
    },
    {
        label: 'Profesores',
        role: 'profesor',
        count: 'teachers',
        icon: BookOpen,
    },
    {
        label: 'Estudiantes',
        role: 'estudiante',
        count: 'students',
        icon: GraduationCap,
    },
    {
        label: 'Acudientes',
        role: 'acudiente',
        count: 'guardians',
        icon: HeartHandshake,
    },
];

const roleStyles: Record<UserRole, string> = {
    admin: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    profesor: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    estudiante: 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
    acudiente: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
};

function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
}

export default function UserIndex({
    users,
    filters: activeFilters,
    counts,
}: {
    users: Paginator;
    filters: { role: UserRole | null; search: string };
    counts: Counts;
}) {
    const [passwordUser, setPasswordUser] = useState<UserItem | null>(null);

    return (
        <>
            <Head title="Usuarios" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                        <UsersRound className="size-4" /> Administración
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Usuarios
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Consulta las personas registradas y su rol dentro de la
                        comunidad educativa.
                    </p>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    {filters.map(({ label, role, count, icon: Icon }) => {
                        const isActive = activeFilters.role === role;
                        return (
                            <Link
                                key={label}
                                href={usersRoute({
                                    query: {
                                        ...(role ? { role } : {}),
                                        ...(activeFilters.search
                                            ? { search: activeFilters.search }
                                            : {}),
                                    },
                                })}
                                preserveScroll
                                className={`group rounded-2xl border p-4 transition focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:outline-none ${isActive ? 'border-indigo-600 bg-indigo-600 text-white shadow-md shadow-indigo-600/15' : 'bg-card hover:-translate-y-0.5 hover:border-indigo-500/30 hover:bg-indigo-500/5'}`}
                            >
                                <div className="flex items-start justify-between">
                                    <span
                                        className={`flex size-9 items-center justify-center rounded-xl ${isActive ? 'bg-white/15' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400'}`}
                                    >
                                        <Icon className="size-4" />
                                    </span>
                                    <span className="text-2xl font-semibold tabular-nums">
                                        {counts[count]}
                                    </span>
                                </div>
                                <p
                                    className={`mt-3 text-sm font-medium ${isActive ? 'text-white' : 'text-muted-foreground group-hover:text-foreground'}`}
                                >
                                    {label}
                                </p>
                            </Link>
                        );
                    })}
                </div>

                <Form
                    {...usersRoute.form()}
                    className="bg-card flex flex-col gap-3 rounded-2xl border p-4 shadow-sm sm:flex-row"
                >
                    {({ processing, errors }) => (
                        <>
                            {activeFilters.role && (
                                <input
                                    type="hidden"
                                    name="role"
                                    value={activeFilters.role}
                                />
                            )}
                            <div className="relative flex-1">
                                <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    type="search"
                                    name="search"
                                    defaultValue={activeFilters.search}
                                    placeholder="Buscar por nombre o correo..."
                                    className="h-10 pl-9"
                                    maxLength={100}
                                    aria-label="Buscar usuarios por nombre o correo"
                                    aria-invalid={Boolean(errors.search)}
                                    disabled={processing}
                                />
                                {errors.search && (
                                    <p className="text-destructive mt-1.5 text-xs">
                                        {errors.search}
                                    </p>
                                )}
                            </div>
                            <div className="flex gap-2">
                                {activeFilters.search && (
                                    <Button asChild variant="ghost">
                                        <Link
                                            href={usersRoute({
                                                query: activeFilters.role
                                                    ? {
                                                          role: activeFilters.role,
                                                      }
                                                    : {},
                                            })}
                                        >
                                            <X /> Limpiar
                                        </Link>
                                    </Button>
                                )}
                                <Button
                                    type="submit"
                                    className="min-w-28 bg-indigo-600 text-white hover:bg-indigo-700"
                                    disabled={processing}
                                    aria-busy={processing}
                                >
                                    {processing ? (
                                        <>
                                            <Spinner /> Buscando…
                                        </>
                                    ) : (
                                        <>
                                            <Search /> Buscar
                                        </>
                                    )}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="flex items-center justify-between border-b px-5 py-4">
                        <div>
                            <h2 className="font-semibold">
                                Directorio de usuarios
                            </h2>
                            <p className="text-muted-foreground mt-1 text-xs">
                                {users.total} registros encontrados
                            </p>
                        </div>
                        <Badge variant="outline">
                            {activeFilters.role
                                ? filters.find(
                                      (filter) =>
                                          filter.role === activeFilters.role,
                                  )?.label
                                : 'Todos los roles'}
                        </Badge>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[720px] text-left text-sm">
                            <thead className="bg-muted/40 text-muted-foreground text-xs">
                                <tr>
                                    <th className="px-5 py-3 font-medium">
                                        Usuario
                                    </th>
                                    <th className="px-5 py-3 font-medium">
                                        Rol
                                    </th>
                                    <th className="px-5 py-3 font-medium">
                                        Estado
                                    </th>
                                    <th className="px-5 py-3 font-medium">
                                        Registro
                                    </th>
                                    <th className="px-5 py-3 text-right font-medium">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {users.data.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="transition-colors hover:bg-indigo-500/4"
                                    >
                                        <td className="px-5 py-4">
                                            <div className="flex items-center gap-3">
                                                <Avatar className="size-9">
                                                    <AvatarFallback className="bg-indigo-500/10 text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                                                        {initials(user.name)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="font-medium">
                                                        {user.name}
                                                    </p>
                                                    <p className="text-muted-foreground mt-0.5 text-xs">
                                                        {user.email}
                                                        {user.personal_email && (
                                                            <span className="text-muted-foreground block text-xs">
                                                                Personal:{' '}
                                                                {
                                                                    user.personal_email
                                                                }
                                                            </span>
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <span
                                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${roleStyles[user.role]}`}
                                            >
                                                {user.role_label}
                                            </span>
                                        </td>
                                        <td className="px-5 py-4">
                                            <span className="text-muted-foreground inline-flex items-center gap-1.5 text-xs">
                                                <span
                                                    className={`size-2 rounded-full ${user.is_verified ? 'bg-emerald-500' : 'bg-amber-500'}`}
                                                />
                                                {user.is_verified
                                                    ? 'Verificado'
                                                    : 'Pendiente'}
                                            </span>
                                        </td>
                                        <td className="text-muted-foreground px-5 py-4 text-xs">
                                            {user.created_at ?? 'Sin fecha'}
                                        </td>
                                        <td className="px-5 py-4 text-right">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setPasswordUser(user)
                                                }
                                            >
                                                <KeyRound /> Cambiar contraseña
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                                {users.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-5 py-14 text-center"
                                        >
                                            <UserRoundCheck className="text-muted-foreground/50 mx-auto size-8" />
                                            <p className="mt-3 font-medium">
                                                No encontramos usuarios
                                            </p>
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                Prueba otra búsqueda o cambia el
                                                filtro seleccionado.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex flex-col gap-3 border-t px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-muted-foreground text-xs">
                            {users.from
                                ? `Mostrando ${users.from}–${users.to} de ${users.total}`
                                : 'Sin resultados'}
                        </p>
                        <div className="flex gap-2">
                            <Button
                                asChild={Boolean(users.prev_page_url)}
                                variant="outline"
                                size="sm"
                                disabled={!users.prev_page_url}
                            >
                                {users.prev_page_url ? (
                                    <Link
                                        href={users.prev_page_url}
                                        preserveScroll
                                    >
                                        <ChevronLeft /> Anterior
                                    </Link>
                                ) : (
                                    <>
                                        <ChevronLeft /> Anterior
                                    </>
                                )}
                            </Button>
                            <Button
                                asChild={Boolean(users.next_page_url)}
                                variant="outline"
                                size="sm"
                                disabled={!users.next_page_url}
                            >
                                {users.next_page_url ? (
                                    <Link
                                        href={users.next_page_url}
                                        preserveScroll
                                    >
                                        Siguiente <ChevronRight />
                                    </Link>
                                ) : (
                                    <>
                                        Siguiente <ChevronRight />
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                </section>

                <Dialog
                    open={passwordUser !== null}
                    onOpenChange={(open) => !open && setPasswordUser(null)}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Actualizar contraseña</DialogTitle>
                            <DialogDescription>
                                Define una nueva contraseña para{' '}
                                {passwordUser?.name}. El usuario podrá usarla en
                                su próximo inicio de sesión.
                            </DialogDescription>
                        </DialogHeader>
                        {passwordUser && (
                            <Form
                                {...UserController.updatePassword.form(
                                    passwordUser,
                                )}
                                resetOnSuccess
                                onSuccess={() => setPasswordUser(null)}
                                className="space-y-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <label
                                                htmlFor="admin_password"
                                                className="text-sm font-medium"
                                            >
                                                Nueva contraseña
                                            </label>
                                            <PasswordInput
                                                id="admin_password"
                                                name="password"
                                                autoComplete="new-password"
                                                required
                                            />
                                            <InputError
                                                message={errors.password}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <label
                                                htmlFor="admin_password_confirmation"
                                                className="text-sm font-medium"
                                            >
                                                Confirmar contraseña
                                            </label>
                                            <PasswordInput
                                                id="admin_password_confirmation"
                                                name="password_confirmation"
                                                autoComplete="new-password"
                                                required
                                            />
                                        </div>
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    setPasswordUser(null)
                                                }
                                            >
                                                Cancelar
                                            </Button>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {processing
                                                    ? 'Actualizando…'
                                                    : 'Actualizar contraseña'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

UserIndex.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Usuarios', href: usersRoute() },
    ],
};
