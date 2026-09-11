import { Form, Head } from '@inertiajs/react';
import {
    BookOpen,
    GraduationCap,
    HeartHandshake,
    ShieldCheck,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = { status?: string; canResetPassword: boolean };
type Role = { name: string; icon: LucideIcon };

const roles: Role[] = [
    { name: 'Administrador', icon: ShieldCheck },
    { name: 'Profesor', icon: BookOpen },
    { name: 'Estudiante', icon: GraduationCap },
    { name: 'Acudiente', icon: HeartHandshake },
];

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Iniciar sesión" />
            <div className="mb-7 grid grid-cols-2 gap-2">
                {roles.map(({ name, icon: Icon }) => (
                    <div
                        key={name}
                        className="bg-muted/35 text-muted-foreground flex items-center gap-2 rounded-lg border px-3 py-2 text-xs"
                    >
                        <Icon className="size-3.5 text-indigo-600 dark:text-indigo-400" />
                        <span>{name}</span>
                    </div>
                ))}
            </div>
            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Correo electrónico</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="nombre@colegio.edu"
                                className="h-11"
                                disabled={processing}
                            />
                            <InputError message={errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password">Contraseña</Label>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="ml-auto text-xs font-medium text-indigo-600 underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400"
                                        tabIndex={5}
                                    >
                                        ¿Olvidaste tu contraseña?
                                    </TextLink>
                                )}
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Ingresa tu contraseña"
                                className="h-11"
                                disabled={processing}
                            />
                            <InputError message={errors.password} />
                        </div>
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                                disabled={processing}
                            />
                            <Label
                                htmlFor="remember"
                                className="text-muted-foreground cursor-pointer font-normal"
                            >
                                Mantener mi sesión iniciada
                            </Label>
                        </div>
                        <Button
                            type="submit"
                            className="mt-1 h-11 w-full bg-indigo-600 text-white hover:bg-indigo-700"
                            tabIndex={4}
                            disabled={processing}
                            aria-busy={processing}
                            data-test="login-button"
                        >
                            {processing ? (
                                <>
                                    <Spinner />
                                    Iniciando sesión…
                                </>
                            ) : (
                                'Iniciar sesión'
                            )}
                        </Button>
                    </>
                )}
            </Form>
            {status && (
                <div className="mt-5 rounded-lg bg-emerald-500/10 px-3 py-2 text-center text-sm font-medium text-emerald-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Bienvenido de nuevo',
    description: 'Ingresa tus datos para acceder a tu espacio escolar.',
};
