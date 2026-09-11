import { Head, useForm } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { studentReports } from '@/routes/teacher';
import { store } from '@/routes/teacher/student-reports';

export default function StudentReports({ students }: { students: { id: number; name: string; course: string }[] }) {
    const form = useForm({ student_id: students[0]?.id.toString() ?? '', category: 'bienestar', title: '', details: '' });
    return <><Head title="Reportes de estudiantes" /><main className="flex flex-col gap-6 p-4 sm:p-8">
        <div><p className="text-sm font-medium text-indigo-600">Seguimiento</p><h1 className="mt-2 text-3xl font-semibold">Reportar una novedad</h1><p className="text-muted-foreground mt-2">El estudiante y todos sus acudientes recibirán la información.</p></div>
        <form className="bg-card flex max-w-3xl flex-col gap-4 rounded-2xl border p-5" onSubmit={(event) => { event.preventDefault(); if (form.data.student_id) form.post(store.url(Number(form.data.student_id)), { onSuccess: () => form.reset('title', 'details') }); }}>
            <label className="flex flex-col gap-2 text-sm">Estudiante<select required className="bg-background rounded-md border p-2" value={form.data.student_id} onChange={(event) => form.setData('student_id', event.target.value)}><option value="">Seleccionar</option>{students.map((student) => <option key={student.id} value={student.id}>{student.name} · {student.course}</option>)}</select></label>
            <label className="flex flex-col gap-2 text-sm">Tipo<select className="bg-background rounded-md border p-2" value={form.data.category} onChange={(event) => form.setData('category', event.target.value)}><option value="bienestar">Bienestar</option><option value="salud">Salud</option><option value="academico">Académico</option><option value="convivencia">Convivencia</option></select></label>
            <label className="flex flex-col gap-2 text-sm">Título<Input required maxLength={160} value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} placeholder="Ej. El estudiante se siente mal" /></label>
            <label className="flex flex-col gap-2 text-sm">Detalle<textarea required maxLength={3000} className="bg-background min-h-32 rounded-md border p-3" value={form.data.details} onChange={(event) => form.setData('details', event.target.value)} /></label>
            {Object.entries(form.errors).map(([key, error]) => <p key={key} className="text-destructive text-sm">{error}</p>)}
            <Button disabled={form.processing || !students.length} className="self-start"><Send /> Enviar reporte</Button>
        </form>
        {!students.length && <p className="text-muted-foreground">No tienes estudiantes asignados.</p>}
    </main></>;
}
StudentReports.layout = { breadcrumbs: [{ title: 'Panel general', href: dashboard() }, { title: 'Reportes', href: studentReports() }] };
