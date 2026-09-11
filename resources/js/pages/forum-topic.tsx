import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { index, reply } from '@/routes/forum';

type Post = {
    id: number;
    body: string;
    user: { name: string };
    created_at: string;
};
export default function ForumTopic({
    topic,
    replies,
}: {
    topic: Post & { title: string };
    replies: {
        data: Post[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}) {
    const form = useForm({ body: '' });
    return (
        <>
            <Head title={topic.title} />
            <main className="flex flex-col gap-5 p-4 sm:p-8">
                <Link href={index()}>Volver al foro</Link>
                <article className="bg-card rounded-xl border p-5">
                    <h1 className="text-2xl font-semibold">{topic.title}</h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        {topic.user.name}
                    </p>
                    <p className="mt-4 break-words whitespace-pre-wrap">
                        {topic.body}
                    </p>
                </article>
                <h2 className="font-semibold">Respuestas</h2>
                {replies.data.map((post) => (
                    <article
                        key={post.id}
                        className="bg-card rounded-xl border p-5"
                    >
                        <p className="text-sm font-medium">
                            {post.user.name} ·{' '}
                            {new Date(post.created_at).toLocaleString('es-CO')}
                        </p>
                        <p className="mt-3 break-words whitespace-pre-wrap">
                            {post.body}
                        </p>
                    </article>
                ))}
                {replies.data.length === 0 && (
                    <p className="text-muted-foreground">
                        Sé el primero en responder.
                    </p>
                )}
                <div className="flex gap-4">
                    {replies.prev_page_url && (
                        <Link href={replies.prev_page_url}>Anterior</Link>
                    )}
                    {replies.next_page_url && (
                        <Link href={replies.next_page_url}>Siguiente</Link>
                    )}
                </div>
                <form
                    className="flex flex-col gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(reply.url(topic.id), {
                            preserveScroll: true,
                            onSuccess: () => form.reset(),
                        });
                    }}
                >
                    <label className="flex flex-col gap-2">
                        Tu respuesta
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
                    {form.errors.body && (
                        <p role="alert" className="text-destructive">
                            {form.errors.body}
                        </p>
                    )}
                    <Button disabled={form.processing} className="self-start">
                        Responder
                    </Button>
                </form>
            </main>
        </>
    );
}
ForumTopic.layout = { breadcrumbs: [{ title: 'Foro', href: index() }] };
