import { Head, usePage } from '@inertiajs/react';
import LandingFeatures from '@/components/landing/landing-features';
import LandingDemoAccounts from '@/components/landing/landing-demo-accounts';
import LandingHero from '@/components/landing/landing-hero';
import LandingNavigation from '@/components/landing/landing-navigation';
import { appName } from '@/lib/app-name';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Inicio" />
            <div className="bg-background text-foreground min-h-screen overflow-hidden">
                <div className="pointer-events-none absolute inset-x-0 top-0 -z-0 h-[42rem] bg-[radial-gradient(circle_at_50%_0%,oklch(0.92_0.07_265_/_0.65),transparent_62%)] dark:bg-[radial-gradient(circle_at_50%_0%,oklch(0.28_0.08_265_/_0.45),transparent_65%)]" />
                <LandingNavigation user={auth.user} />
                <main className="relative z-10">
                    <LandingHero user={auth.user} />
                    <LandingDemoAccounts />
                    <LandingFeatures />
                </main>
                <footer className="border-border/70 relative z-10 border-t px-6 py-7">
                    <div className="text-muted-foreground mx-auto flex max-w-7xl flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            © {new Date().getFullYear()} {appName}. Gestión
                            escolar, más humana.
                        </p>
                        <p>Diseñado para toda la comunidad educativa.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
