import { Link } from '@inertiajs/react';
import {
    GraduationCap,
    HeartHandshake,
    KeyRound,
    Presentation,
    ShieldCheck,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';

type DemoAccount = {
    role: string;
    email: string;
    icon: LucideIcon;
    color: string;
};

const demoAccounts: DemoAccount[] = [
    {
        role: 'Administrador',
        email: 'admin@aula.test',
        icon: ShieldCheck,
        color: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
    },
    {
        role: 'Profesor',
        email: 'profesor@aula.test',
        icon: Presentation,
        color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
    {
        role: 'Estudiante',
        email: 'estudiante@aula.test',
        icon: GraduationCap,
        color: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    },
    {
        role: 'Acudiente',
        email: 'acudiente@aula.test',
        icon: HeartHandshake,
        color: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
    },
];

export default function LandingDemoAccounts() {
    return (
        <section
            id="demo"
            className="border-border/60 border-t px-6 py-20 lg:px-8"
        >
            <div className="mx-auto max-w-7xl">
                <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                            Explora todos los roles
                        </p>
                        <h2 className="mt-2 text-3xl font-semibold tracking-tight">
                            Usuarios de prueba
                        </h2>
                        <p className="text-muted-foreground mt-3 max-w-2xl">
                            Ingresa con cualquiera de estas cuentas para conocer
                            la experiencia completa de la plataforma.
                        </p>
                    </div>
                    <div className="bg-muted flex items-center gap-2 rounded-xl px-4 py-3 text-sm">
                        <KeyRound className="size-4 text-indigo-600" />
                        Contraseña para todos: <strong>password</strong>
                    </div>
                </div>
                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {demoAccounts.map(({ role, email, icon: Icon, color }) => (
                        <article
                            key={role}
                            className="bg-card rounded-2xl border p-5 shadow-sm"
                        >
                            <span
                                className={`flex size-10 items-center justify-center rounded-xl ${color}`}
                            >
                                <Icon className="size-5" />
                            </span>
                            <h3 className="mt-5 font-semibold">{role}</h3>
                            <code className="text-muted-foreground mt-2 block text-xs break-all">
                                {email}
                            </code>
                            <Button
                                asChild
                                variant="outline"
                                className="mt-5 w-full"
                            >
                                <Link href={login()}>Iniciar sesión</Link>
                            </Button>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}
