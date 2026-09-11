import { Link } from '@inertiajs/react';
import { GraduationCap, Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/hooks/use-appearance';
import { appName } from '@/lib/app-name';
import { dashboard, home, login } from '@/routes';
import type { User } from '@/types';

export default function LandingNavigation({ user }: { user: User | null }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    return (
        <header className="relative z-20 px-6 py-5 lg:px-8">
            <nav
                className="mx-auto flex max-w-7xl items-center justify-between"
                aria-label="Navegación principal"
            >
                <Link
                    href={home()}
                    className="flex items-center gap-2.5 font-semibold tracking-tight"
                >
                    <span className="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm shadow-indigo-600/25">
                        <GraduationCap className="size-5" />
                    </span>
                    <span className="text-lg">{appName}</span>
                </Link>
                <div className="text-muted-foreground hidden items-center gap-8 text-sm md:flex">
                    <a
                        href="#funciones"
                        className="hover:text-foreground transition-colors"
                    >
                        Funciones
                    </a>
                    <a
                        href="#plataforma"
                        className="hover:text-foreground transition-colors"
                    >
                        Plataforma
                    </a>
                    <a
                        href="#comunidad"
                        className="hover:text-foreground transition-colors"
                    >
                        Comunidad
                    </a>
                </div>
                <div className="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="rounded-full"
                        onClick={() =>
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        }
                        aria-label={`Cambiar a modo ${resolvedAppearance === 'dark' ? 'claro' : 'oscuro'}`}
                    >
                        {resolvedAppearance === 'dark' ? <Sun /> : <Moon />}
                    </Button>
                    <Button
                        asChild
                        variant="ghost"
                        className="hidden sm:inline-flex"
                    >
                        <Link href={user ? dashboard() : login()}>
                            {user ? 'Ir al panel' : 'Iniciar sesión'}
                        </Link>
                    </Button>
                    <Button
                        asChild
                        className="rounded-full bg-indigo-600 px-5 text-white hover:bg-indigo-700"
                    >
                        <Link href={user ? dashboard() : login()}>
                            {user ? `Abrir ${appName}` : `Explorar ${appName}`}
                        </Link>
                    </Button>
                </div>
            </nav>
        </header>
    );
}
