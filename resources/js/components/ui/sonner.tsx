import { useFlashToast } from '@/hooks/use-flash-toast';
import { useAppearance } from '@/hooks/use-appearance';
import { Toaster as Sonner, type ToasterProps } from 'sonner';
import {
    CheckCircle2,
    CircleAlert,
    Info,
    TriangleAlert,
} from 'lucide-react';

function Toaster({ ...props }: ToasterProps) {
    const { appearance } = useAppearance();

    useFlashToast();

    return (
        <Sonner
            theme={appearance}
            closeButton
            icons={{
                success: <CheckCircle2 className="size-5 text-emerald-500" />,
                info: <Info className="size-5 text-indigo-500" />,
                warning: <TriangleAlert className="size-5 text-amber-500" />,
                error: <CircleAlert className="size-5 text-rose-500" />,
            }}
            className="toaster group"
            position="bottom-right"
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                } as React.CSSProperties
            }
            toastOptions={{
                classNames: {
                    toast: 'group/toast !rounded-2xl !border !bg-card !px-4 !py-3 !shadow-xl',
                    title: '!text-sm !font-semibold !text-card-foreground',
                    description: '!mt-1 !text-xs !text-muted-foreground',
                    success: '!border-emerald-500/30 !shadow-emerald-950/10',
                    info: '!border-indigo-500/30 !shadow-indigo-950/10',
                    warning: '!border-amber-500/35 !shadow-amber-950/10',
                    error: '!border-rose-500/35 !shadow-rose-950/10',
                },
            }}
            {...props}
        />
    );
}

export { Toaster };
