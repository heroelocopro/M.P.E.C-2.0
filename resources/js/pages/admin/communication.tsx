import { Head, useForm } from '@inertiajs/react';
import { Mail, Send, UsersRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import communication from '@/routes/admin/communication';

export default function Communication({ recipient_count: recipientCount }: { recipient_count: number }) {
    const form = useForm({ subject: '', message: '' });
    return <>
        <Head title="Correo comunitario" />
        <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <div><div className="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400"><Mail className="size-4" /> Comunicaciones</div><h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Correo comunitario</h1><p className="text-muted-foreground mt-2 max-w-2xl text-sm">Envía un comunicado a toda la comunidad con correo registrado. Los destinatarios reciben el mensaje en copia oculta.</p></div>
            <div className="grid gap-4 sm:grid-cols-3"><div className="bg-card flex items-center gap-3 rounded-2xl border p-5 shadow-sm sm:col-span-1"><UsersRound className="size-5 text-indigo-500" /><div><p className="text-muted-foreground text-xs">Destinatarios</p><p className="text-2xl font-bold">{recipientCount}</p></div></div></div>
            <section className="bg-card max-w-3xl rounded-2xl border p-5 shadow-sm sm:p-6"><form className="grid gap-4" onSubmit={(event) => { event.preventDefault(); form.post(communication.sendAll.url(), { onSuccess: () => form.reset() }); }}><label className="grid gap-2 text-sm font-medium">Asunto<input className="bg-background rounded-lg border px-3 py-2 font-normal" value={form.data.subject} onChange={(event) => form.setData('subject', event.target.value)} /></label><label className="grid gap-2 text-sm font-medium">Mensaje<textarea className="bg-background min-h-48 rounded-lg border p-3 font-normal" value={form.data.message} onChange={(event) => form.setData('message', event.target.value)} /></label>{form.errors.subject && <p className="text-destructive text-xs">{form.errors.subject}</p>}{form.errors.message && <p className="text-destructive text-xs">{form.errors.message}</p>}<Button disabled={form.processing || recipientCount === 0} className="w-fit bg-indigo-600 text-white hover:bg-indigo-700"><Send /> {form.processing ? 'Enviando…' : 'Enviar a toda la comunidad'}</Button></form></section>
        </div>
    </>;
}

Communication.layout = { breadcrumbs: [{ title: 'Panel general', href: dashboard() }, { title: 'Correo comunitario', href: '/admin/comunicaciones' }] };