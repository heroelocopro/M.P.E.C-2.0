import { GraduationCap } from 'lucide-react';
import { appName } from '@/lib/app-name';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm shadow-indigo-600/25">
                <GraduationCap className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-base leading-tight font-semibold tracking-tight">
                    {appName}
                </span>
                <span className="text-sidebar-foreground/55 truncate text-[10px] leading-tight">
                    Plataforma escolar
                </span>
            </div>
        </>
    );
}
