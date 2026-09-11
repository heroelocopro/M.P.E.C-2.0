import { Link, router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { notifications } from '@/routes';
import { read, readAll } from '@/routes/notifications';

export function NotificationMenu() {
    const { notificationCenter } = usePage().props;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative rounded-xl"
                    aria-label="Abrir notificaciones"
                >
                    <Bell className="size-5" />
                    {notificationCenter.unread_count > 0 && (
                        <span className="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-[10px] leading-4 font-semibold text-white">
                            {Math.min(notificationCenter.unread_count, 99)}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between p-3">
                    <DropdownMenuLabel className="p-0">
                        Notificaciones
                    </DropdownMenuLabel>
                    {notificationCenter.unread_count > 0 && (
                        <button
                            className="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                            onClick={() => router.patch(readAll.url())}
                        >
                            Marcar todas
                        </button>
                    )}
                </div>
                <DropdownMenuSeparator className="m-0" />
                <div className="max-h-80 overflow-y-auto">
                    {notificationCenter.recent.map((notification) => (
                        <button
                            key={notification.id}
                            className="hover:bg-muted/60 flex w-full gap-3 border-b p-3 text-left last:border-0"
                            onClick={() =>
                                router.patch(read.url(notification.id))
                            }
                        >
                            <span
                                className={`mt-1 size-2 shrink-0 rounded-full ${notification.read_at ? 'bg-muted-foreground/25' : 'bg-indigo-600'}`}
                            />
                            <span className="min-w-0">
                                <span className="block truncate text-sm font-medium">
                                    {notification.title}
                                </span>
                                <span className="text-muted-foreground mt-0.5 line-clamp-2 block text-xs">
                                    {notification.message}
                                </span>
                            </span>
                        </button>
                    ))}
                    {notificationCenter.recent.length === 0 && (
                        <div className="text-muted-foreground flex flex-col items-center gap-2 px-4 py-8 text-center text-sm">
                            <CheckCheck className="size-6" />
                            No tienes notificaciones.
                        </div>
                    )}
                </div>
                <DropdownMenuSeparator className="m-0" />
                <Link
                    href={notifications()}
                    className="block p-3 text-center text-xs font-medium text-indigo-600 hover:bg-indigo-500/5 dark:text-indigo-400"
                >
                    Ver todas las notificaciones
                </Link>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
