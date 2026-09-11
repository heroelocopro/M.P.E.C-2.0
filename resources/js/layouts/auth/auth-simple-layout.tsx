import { Link } from '@inertiajs/react';
import { ArrowLeft, GraduationCap, Moon, Sparkles, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/hooks/use-appearance';
import { appName } from '@/lib/app-name';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    return (
        <div className="bg-background relative min-h-svh overflow-hidden px-5 py-5 sm:px-8 sm:py-8">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_15%_10%,oklch(0.92_0.07_265_/_0.7),transparent_34%)] dark:bg-[radial-gradient(circle_at_15%_10%,oklch(0.27_0.08_265_/_0.45),transparent_38%)]" />
            <div className="bg-card relative mx-auto grid min-h-[calc(100svh-2.5rem)] max-w-6xl overflow-hidden rounded-3xl border shadow-2xl shadow-indigo-950/10 sm:min-h-[calc(100svh-4rem)] lg:grid-cols-[1.05fr_0.95fr] dark:shadow-black/30">
                <aside className="relative hidden overflow-hidden bg-indigo-600 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <div className="absolute -top-24 -right-24 size-80 rounded-full border-[60px] border-white/5" />
                    <div className="absolute -bottom-32 -left-24 size-96 rounded-full bg-indigo-400/20 blur-3xl" />
                    <Link
                        href={home()}
                        className="relative flex w-fit items-center gap-2.5 text-lg font-semibold"
                    >
                        <span className="flex size-10 items-center justify-center rounded-xl bg-white/15">
                            <GraduationCap className="size-6" />
                        </span>
                        {appName}
                    </Link>
                    <div className="relative max-w-md">
                        <span className="mb-6 flex size-11 items-center justify-center rounded-xl bg-white/15">
                            <Sparkles className="size-5" />
                        </span>
                        <h2 className="text-4xl leading-tight font-semibold tracking-[-0.035em]">
                            Tu comunidad educativa, siempre conectada.
                        </h2>
                        <p className="mt-5 leading-7 text-indigo-100">
                            Gestiona el día a día escolar de forma clara, segura
                            y desde un solo lugar.
                        </p>
                    </div>
                    <div className="relative flex items-center gap-3 text-sm text-indigo-100">
                        <div className="flex -space-x-2">
                            {['AD', 'PR', 'ES', 'AC'].map((role) => (
                                <span
                                    key={role}
                                    className="flex size-8 items-center justify-center rounded-full border-2 border-indigo-600 bg-indigo-500 text-[9px] font-semibold"
                                >
                                    {role}
                                </span>
                            ))}
                        </div>
                        <span>Un espacio para cada rol</span>
                    </div>
                </aside>
                <main className="flex flex-col p-6 sm:p-10 lg:p-12">
                    <div className="flex items-center justify-between">
                        <Button
                            asChild
                            variant="ghost"
                            className="text-muted-foreground -ml-3"
                        >
                            <Link href={home()}>
                                <ArrowLeft /> Volver al inicio
                            </Link>
                        </Button>
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
                    </div>
                    <div className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-10">
                        <div className="mb-8 space-y-2">
                            <h1 className="text-3xl font-semibold tracking-tight">
                                {title}
                            </h1>
                            <p className="text-muted-foreground text-sm leading-6">
                                {description}
                            </p>
                        </div>
                        {children}
                    </div>
                    <p className="text-muted-foreground text-center text-xs">
                        Acceso seguro para la comunidad educativa.
                    </p>
                </main>
            </div>
        </div>
    );
}
