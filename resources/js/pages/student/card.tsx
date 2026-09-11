import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { QrCode, ShieldCheck } from 'lucide-react';
import { QRCodeCanvas } from 'qrcode.react';

export default function StudentCard({
    student,
}: {
    student: { name: string; document: string; card_url: string };
}) {
    return (
        <>
            <Head title="Mi QR" />
            <div className="flex flex-1 items-center justify-center p-4 sm:p-8">
                <section className="relative w-full max-w-md overflow-hidden rounded-3xl bg-linear-to-br from-indigo-700 via-indigo-800 to-slate-950 p-6 text-white shadow-2xl shadow-indigo-950/20 sm:p-8">
                    <div className="absolute -top-16 -right-12 size-48 rounded-full bg-white/10" />
                    <div className="relative text-center">
                        <Badge className="border-white/20 bg-white/10 text-white">
                            <QrCode /> Carnet digital
                        </Badge>
                        <h1 className="mt-6 text-2xl font-bold">
                            {student.name}
                        </h1>
                        <p className="mt-1 text-sm text-indigo-200">
                            Documento {student.document}
                        </p>
                        <div className="mx-auto mt-7 inline-flex rounded-2xl bg-white p-4">
                            <QRCodeCanvas
                                value={student.card_url}
                                size={220}
                                includeMargin
                            />
                        </div>
                        <div className="mt-6 flex items-center justify-center gap-2 text-xs text-indigo-200">
                            <ShieldCheck className="size-4" /> Identificación
                            segura para ingreso institucional
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

StudentCard.layout = {
    breadcrumbs: [
        { title: 'Panel general', href: dashboard() },
        { title: 'Mi QR', href: '/estudiante/mi-qr' },
    ],
};
