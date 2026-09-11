import type { Auth } from '@/types/auth';

type AppNotification = {
    id: string;
    title: string;
    message: string;
    category: string;
    url: string | null;
    read_at: string | null;
    created_at: string;
};

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            gradingScale: { minimum: number; maximum: number; passing: number };
            auth: Auth;
            notificationCenter: {
                unread_count: number;
                recent: AppNotification[];
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
