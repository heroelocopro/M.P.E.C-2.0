import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, show, store } from '@/routes/forum';

type Topic = {
    id: number;
    title: string;
    user: { name: string };
    replies_count: number;
    classroom_id: number;
};
export default function Forum({
    classrooms,
    topics,
}: {
    classrooms: { id: number; label: string }[];
    topics: {
        data: Topic[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}) {
    const form = useForm({
        classroom_id: classrooms[0]?.id.toString() ?? '',
        title: '',
        body: '',
    });
    return (
        <>
            <Head title="Foro" />
            <main className="flex flex-col gap-6 p-4 sm:p-8">
                <div>
                    <h1 className="text-3xl font-semibold">Foro del curso</h1>
                    <p className="text-muted-foreground mt-2">
                        Comparte preguntas e ideas con tus compañeros y
                        profesores.
                    </p>
                </div>
                {classrooms.length > 0 && (
                    <form
                        className="bg-card flex flex-col gap-4 rounded-xl border p-5"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post(store.url(), {
                                onSuccess: () => form.reset('title', 'body'),
                            });
                        }}
                    >
                        <h2 className="font-semibold">Nuevo tema</h2>
                        <label className="flex flex-col gap-2 text-sm">
                            Curso
                            <select
                                className="bg-background rounded-md border p-2"
                                value={form.data.classroom_id}
                                onChange={(event) =>
                                    form.setData(
                                        'classroom_id',
                                        event.target.value,
                                    )
                                }
                            >
                                {classrooms.map((course) => (
                                    <option key={course.id} value={course.id}>
                                        {course.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="flex flex-col gap-2 text-sm">
                            Título
                            <Input
                                required
                                maxLength={160}
                                value={form.data.title}
                                onChange={(event) =>
                                    form.setData('title', event.target.value)
                                }
                            />
                        </label>
                        <label className="flex flex-col gap-2 text-sm">
                            Mensaje
                            <textarea
                                required
                                maxLength={5000}
                                className="bg-background min-h-28 rounded-md border p-3"
                                value={form.data.body}
                                onChange={(event) =>
                                    form.setData('body', event.target.value)
                                }
                            />
                        </label>
                        {Object.values(form.errors).map((error) => (
                            <p
                                key={error}
                                role="alert"
                                className="text-destructive text-sm"
                            >
                                {error}
                            </p>
                        ))}
                        <Button
                            disabled={form.processing}
                            className="self-start"
                        >
                            Publicar tema
                        </Button>
                    </form>
                )}
                {topics.data.map((topic) => (
                    <Link
                        key={topic.id}
                        href={show(topic.id)}
                        className="bg-card rounded-xl border p-5 hover:border-indigo-400"
                    >
                        <h2 className="font-semibold">{topic.title}</h2>
                        <p className="text-muted-foreground mt-2 text-sm">
                            {topic.user.name} ·{' '}
                            {
                                classrooms.find(
                                    (course) =>
                                        course.id === topic.classroom_id,
                                )?.label
                            }{' '}
                            · {topic.replies_count} respuestas
                        </p>
                    </Link>
                ))}
                {topics.data.length === 0 && (
                    <p className="text-muted-foreground">
                        Todavía no hay temas en tus cursos.
                    </p>
                )}
                <div className="flex gap-4">
                    {topics.prev_page_url && (
                        <Link href={topics.prev_page_url}>Anterior</Link>
                    )}
                    {topics.next_page_url && (
                        <Link href={topics.next_page_url}>Siguiente</Link>
                    )}
                </div>
            </main>
        </>
    );
}
Forum.layout = { breadcrumbs: [{ title: 'Foro', href: index() }] };
