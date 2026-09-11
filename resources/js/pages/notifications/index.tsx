import { Head, Link, router } from '@inertiajs/react';
import { Bell, CheckCheck, Info, Megaphone } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard, notifications as notificationsRoute } from '@/routes';
import { read, readAll } from '@/routes/notifications';

type Notification = {
    id: string;
    title: string;
    message: string;
    category: string;
    url: string | null;
    read_at: string | null;
    created_at: string;
};
type Pagination = {
    data: Notification[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function NotificationIndex({
    notifications,
}: {
    notifications: Pagination;
}) {
    const unreadCount = notifications.data.filter(
        (notification) => !notification.read_at,
    ).length;
    const formatDate = (value: string) =>
        new Intl.DateTimeFormat('es-CO', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(value));

    return (
        <>
            <Head title="Notificaciones" />
            <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <Bell className="size-4" /> Centro de novedades
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Notificaciones
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Avisos y actividad relevante de tu cuenta.
                        </p>
                    </div>
                    {unreadCount > 0 && (
                        <Button
                            variant="outline"
                            onClick={() => router.patch(readAll.url())}
                        >
                            <CheckCheck /> Marcar todas como leídas
                        </Button>
                    )}
                </div>
                <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                    {notifications.data.map((notification) => (
                        <article
                            key={notification.id}
                            className={`flex gap-4 border-b p-5 last:border-0 ${notification.read_at ? '' : 'bg-indigo-500/[0.035]'}`}
                        >
                            <span
                                className={`flex size-10 shrink-0 items-center justify-center rounded-xl ${notification.category === 'aviso' ? 'bg-amber-500/10 text-amber-600' : 'bg-indigo-500/10 text-indigo-600'}`}
                            >
                                {notification.category === 'aviso' ? (
                                    <Megaphone className="size-5" />
                                ) : (
                                    <Info className="size-5" />
                                )}
                            </span>
                            <div className="min-w-0 flex-1">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 className="text-sm font-semibold">
                                            {notification.title}
                                        </h2>
                                        <p className="text-muted-foreground mt-1 text-sm leading-6">
                                            {notification.message}
                                        </p>
                                    </div>
                                    {!notification.read_at && (
                                        <span className="mt-1 size-2 shrink-0 rounded-full bg-indigo-600" />
                                    )}
                                </div>
                                <div className="mt-3 flex flex-wrap items-center gap-3">
                                    <time className="text-muted-foreground text-xs">
                                        {formatDate(notification.created_at)}
                                    </time>
                                    {notification.url && (
                                        <Link
                                            href={notification.url}
                                            className="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                                        >
                                            Ver detalle
                                        </Link>
                                    )}
                                    {!notification.read_at && (
                                        <button
                                            onClick={() =>
                                                router.patch(
                                                    read.url(notification.id),
                                                )
                                            }
                                            className="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                                        >
                                            Marcar como leída
                                        </button>
                                    )}
                                </div>
                            </div>
                        </article>
                    ))}
                    {notifications.data.length === 0 && (
                        <div className="flex min-h-72 flex-col items-center justify-center p-8 text-center">
                            <span className="flex size-14 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600">
                                <CheckCheck />
                            </span>
                            <h2 className="mt-4 font-semibold">Todo al día</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                No tienes notificaciones por el momento.
                            </p>
                        </div>
                    )}
                </section>
                {notifications.links.length > 3 && (
                    <nav className="flex flex-wrap justify-center gap-2">
                        {notifications.links.map((link) =>
                            link.url ? (
                                <Link
                                    key={link.label}
                                    href={link.url}
                                    className={`rounded-lg border px-3 py-2 text-xs ${link.active ? 'bg-indigo-600 text-white' : 'bg-card'}`}
                                >
                                    {link.label
                                        .replace('&laquo; Previous', 'Anterior')
                                        .replace('Next &raquo;', 'Siguiente')}
                                </Link>
                            ) : null,
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

NotificationIndex.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Notificaciones', href: notificationsRoute() },
    ],
};
