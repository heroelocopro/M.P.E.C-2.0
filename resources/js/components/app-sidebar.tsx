import { Link, usePage } from '@inertiajs/react';
import {
    BookUser,
    CalendarDays,
    CalendarCheck2,
    ClipboardList,
    GraduationCap,
    HeartPulse,
    LayoutDashboard,
    SlidersHorizontal,
    UserCheck,
    UsersRound,
    Presentation,
    Bell,
    NotebookTabs,
    FileBarChart,
    ScanLine,
    QrCode,
    Send,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { announcements, dashboard } from '@/routes';
import { index as forum } from '@/routes/forum';
import { index as evaluations } from '@/routes/evaluations';
import {
    academicSettings,
    enrollments,
    graduates,
    students,
    users,
    teachers,
    grades as adminGrades,
    institutionalAttendance,
    communication,
} from '@/routes/admin';
import { grades as studentGrades, schedule, tasks } from '@/routes/student';
import {
    attendance,
    grades,
    schedule as teacherSchedule,
    tasks as teacherTasks,
} from '@/routes/teacher';
import { studentReports } from '@/routes/teacher';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Panel general',
        href: dashboard(),
        icon: LayoutDashboard,
    },
];

const adminNavItems: NavItem[] = [
    { title: 'Avisos', href: announcements(), icon: Bell },
    { title: 'Matrículas', href: enrollments(), icon: BookUser },
    { title: 'Estudiantes', href: students(), icon: GraduationCap },
    { title: 'Egresados', href: graduates(), icon: UserCheck },
    { title: 'Profesores', href: teachers(), icon: Presentation },
    { title: 'Notas', href: adminGrades(), icon: FileBarChart },
    {
        title: 'Ingreso institucional',
        href: institutionalAttendance(),
        icon: ScanLine,
    },
    { title: 'Correo comunitario', href: communication(), icon: Send },
    {
        title: 'Usuarios',
        href: users(),
        icon: UsersRound,
    },
    {
        title: 'Configuración académica',
        href: academicSettings(),
        icon: SlidersHorizontal,
    },
];

const studentNavItems: NavItem[] = [
    { title: 'Foro', href: forum(), icon: UsersRound },
    { title: 'Evaluaciones', href: evaluations(), icon: ClipboardList },
    { title: 'Mis tareas', href: tasks(), icon: ClipboardList },
    { title: 'Mi horario', href: schedule(), icon: CalendarDays },
    { title: 'Mis notas', href: studentGrades(), icon: FileBarChart },
    { title: 'Mi QR', href: '/estudiante/mi-qr', icon: QrCode },
    { title: 'Avisos', href: announcements(), icon: Bell },
];

const teacherNavItems: NavItem[] = [
    { title: 'Foro', href: forum(), icon: UsersRound },
    { title: 'Evaluaciones', href: evaluations(), icon: ClipboardList },
    { title: 'Asistencia', href: attendance(), icon: CalendarCheck2 },
    { title: 'Mi horario', href: teacherSchedule(), icon: CalendarDays },
    { title: 'Tareas y proyectos', href: teacherTasks(), icon: ClipboardList },
    { title: 'Notas', href: grades(), icon: NotebookTabs },
    { title: 'Reportes de estudiantes', href: studentReports(), icon: HeartPulse },
    { title: 'Avisos', href: announcements(), icon: Bell },
];

const guardianNavItems: NavItem[] = [
    { title: 'Mis estudiantes', href: '/acudiente/panel', icon: GraduationCap },
    { title: 'Avisos institucionales', href: announcements(), icon: Bell },
];

export function AppSidebar() {
    const { auth } = usePage().props;

    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="[--sidebar-accent:oklch(0.95_0.025_265)] [--sidebar-border:oklch(0.91_0.018_265)] [--sidebar-foreground:oklch(0.24_0.025_265)] [--sidebar:oklch(0.985_0.008_265)] dark:[--sidebar-accent:oklch(0.24_0.035_265)] dark:[--sidebar-border:oklch(0.28_0.025_265)] dark:[--sidebar-foreground:oklch(0.94_0.012_265)] dark:[--sidebar:oklch(0.17_0.018_265)]"
        >
            <SidebarHeader className="border-sidebar-border/70 border-b p-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="h-12 cursor-pointer rounded-xl hover:bg-indigo-500/8 focus-visible:ring-indigo-500"
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-3 px-1 py-3">
                <NavMain items={mainNavItems} />
                {auth.user.role === 'admin' && (
                    <NavMain items={adminNavItems} label="Administración" />
                )}
                {auth.user.role === 'estudiante' && (
                    <NavMain items={studentNavItems} label="Mi espacio" />
                )}
                {auth.user.role === 'profesor' && (
                    <NavMain items={teacherNavItems} label="Docencia" />
                )}
                {auth.user.role === 'acudiente' && (
                    <NavMain items={guardianNavItems} label="Familia" />
                )}
            </SidebarContent>

            <SidebarFooter className="border-sidebar-border/70 border-t p-3">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
