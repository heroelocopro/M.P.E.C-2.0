import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({
    items,
    label = 'Plataforma',
}: {
    items: NavItem[];
    label?: string;
}) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-1">
            <SidebarGroupLabel className="text-sidebar-foreground/45 px-2 text-[10px] font-semibold tracking-[0.12em] uppercase">
                {label}
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            tooltip={{ children: item.title }}
                            className="text-sidebar-foreground/65 h-10 cursor-pointer rounded-xl px-3 transition-[color,background-color,transform] hover:translate-x-0.5 hover:bg-indigo-500/8 hover:text-indigo-700 focus-visible:ring-indigo-500 data-[active=true]:bg-indigo-600 data-[active=true]:text-white data-[active=true]:shadow-sm data-[active=true]:shadow-indigo-600/20 dark:hover:bg-indigo-400/10 dark:hover:text-indigo-300 dark:data-[active=true]:bg-indigo-500 dark:data-[active=true]:text-white"
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
