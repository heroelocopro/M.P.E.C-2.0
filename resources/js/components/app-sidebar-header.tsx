import { Breadcrumbs } from '@/components/breadcrumbs';
import { NotificationMenu } from '@/components/notification-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="bg-background/80 flex h-16 shrink-0 items-center gap-2 border-b border-indigo-500/10 px-6 backdrop-blur-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4 dark:border-indigo-400/10">
            <div className="flex min-w-0 flex-1 items-center gap-2">
                <SidebarTrigger className="text-muted-foreground -ml-1 rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 focus-visible:ring-indigo-500 dark:hover:text-indigo-300" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <NotificationMenu />
        </header>
    );
}
