import { usePage, Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { grade, index, submit } from '@/routes/evaluations';

type Question = {
    type: string;
    prompt: string;
    options?: string[];
    correct?: number;
};
type Attempt = {
    id: number;
    score: number;
    correct_count: number;
    answers: (number | string)[];
    graded_at: string | null;
    manual_scores: Record<string, number> | null;
    feedback: string | null;
    user?: { name: string };
};

function Review({
    evaluationId,
    attempt,
    questions,
}: {
    evaluationId: number;
    attempt: Attempt;
    questions: Question[];
}) {
    const { gradingScale } = usePage().props;
    const form = useForm({
        manual_scores: Object.fromEntries(
            questions.flatMap((question, index) =>
                question.type === 'open'
                    ? [
                          [
                              String(index),
                              String(attempt.manual_scores?.[index] ?? ''),
                          ],
                      ]
                    : [],
            ),
        ),
        feedback: attempt.feedback ?? '',
    });
    const hasOpen = questions.some((question) => question.type === 'open');
    return (
        <form
            className="flex flex-col gap-4 rounded-xl border p-5"
            onSubmit={(event) => {
                event.preventDefault();
                form.patch(
                    grade.url({
                        evaluation: evaluationId,
                        attempt: attempt.id,
                    }),
                    { preserveScroll: true },
                );
            }}
        >
            <h3 className="font-semibold">
                {attempt.user?.name} ·{' '}
                {attempt.graded_at
                    ? `${attempt.score}/${gradingScale.maximum}`
                    : 'Pendiente de revisión'}
            </h3>
            {questions.map((question, questionIndex) => (
                <div
                    key={questionIndex}
                    className="flex flex-col gap-2 border-t pt-3"
                >
                    <p className="font-medium">
                        {questionIndex + 1}. {question.prompt}
                    </p>
                    <p className="break-words whitespace-pre-wrap">
                        {question.type === 'open'
                            ? attempt.answers[questionIndex]
                            : question.options?.[
                                  Number(attempt.answers[questionIndex])
                              ]}
                    </p>
                    {question.type === 'open' && (
                        <label className="flex flex-col gap-2 text-sm">
                            Nota de esta respuesta ({gradingScale.minimum} a{' '}
                            {gradingScale.maximum})
                            <Input
                                required
                                type="number"
                                min={gradingScale.minimum}
                                max={gradingScale.maximum}
                                step="0.01"
                                value={form.data.manual_scores[questionIndex]}
                                onChange={(event) =>
                                    form.setData('manual_scores', {
                                        ...form.data.manual_scores,
                                        [questionIndex]: event.target.value,
                                    })
                                }
                            />
                        </label>
                    )}
                </div>
            ))}
            {hasOpen && (
                <>
                    <label className="flex flex-col gap-2">
                        Observaciones
                        <textarea
                            maxLength={3000}
                            className="bg-background rounded-md border p-3"
                            value={form.data.feedback}
                            onChange={(event) =>
                                form.setData('feedback', event.target.value)
                            }
                        />
                    </label>
                    {Object.entries(form.errors).map(([key, error]) => (
                        <p
                            key={key}
                            role="alert"
                            className="text-destructive text-sm"
                        >
                            {error}
                        </p>
                    ))}
                    <Button disabled={form.processing}>
                        Guardar calificación
                    </Button>
                </>
            )}
        </form>
    );
}

export default function Evaluation({
    evaluation,
    isTeacher,
    questions,
    attempt,
    isExpired,
    results,
}: {
    evaluation: {
        id: number;
        title: string;
        description: string | null;
        due_at: string;
    };
    isTeacher: boolean;
    questions: Question[];
    attempt: Attempt | null;
    isExpired: boolean;
    results: {
        data: Attempt[];
        prev_page_url: string | null;
        next_page_url: string | null;
    } | null;
}) {
    const { gradingScale } = usePage().props;
    const form = useForm({ answers: questions.map(() => '') });
    return (
        <>
            <Head title={evaluation.title} />
            <main className="flex flex-col gap-6 p-4 sm:p-8">
                <Link href={index()}>Volver a evaluaciones</Link>
                <div>
                    <h1 className="text-3xl font-semibold">
                        {evaluation.title}
                    </h1>
                    <p className="mt-3 whitespace-pre-wrap">
                        {evaluation.description}
                    </p>
                    <p className="text-muted-foreground mt-3 text-sm">
                        Vence{' '}
                        {new Date(evaluation.due_at).toLocaleString('es-CO')} ·
                        Un intento · Todas las preguntas tienen el mismo peso.
                    </p>
                </div>
                {attempt && (
                    <div
                        role="status"
                        className="rounded-xl bg-emerald-500/10 p-5"
                    >
                        <p className="font-semibold">
                            {attempt.graded_at
                                ? `Resultado: ${attempt.score}/${gradingScale.maximum}`
                                : 'Entrega recibida. Pendiente de revisión de las respuestas abiertas.'}
                        </p>
                        {attempt.feedback && (
                            <p className="mt-3 whitespace-pre-wrap">
                                {attempt.feedback}
                            </p>
                        )}
                    </div>
                )}
                {isExpired && !attempt && (
                    <p className="text-destructive">
                        Esta evaluación está vencida.
                    </p>
                )}
                <form
                    className="flex flex-col gap-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(submit.url(evaluation.id));
                    }}
                >
                    {questions.map((question, questionIndex) => (
                        <fieldset
                            key={questionIndex}
                            disabled={
                                isTeacher ||
                                !!attempt ||
                                isExpired ||
                                form.processing
                            }
                            className="bg-card flex flex-col gap-3 rounded-xl border p-5"
                        >
                            <legend className="px-2 font-semibold">
                                Pregunta {questionIndex + 1}
                            </legend>
                            <p className="break-words whitespace-pre-wrap">
                                {question.prompt}
                            </p>
                            {question.type === 'open' ? (
                                <label className="flex flex-col gap-2 text-sm">
                                    Tu respuesta
                                    <textarea
                                        required
                                        maxLength={5000}
                                        className="bg-background min-h-32 rounded-md border p-3"
                                        value={
                                            attempt
                                                ? String(
                                                      attempt.answers[
                                                          questionIndex
                                                      ],
                                                  )
                                                : form.data.answers[
                                                      questionIndex
                                                  ]
                                        }
                                        onChange={(event) =>
                                            form.setData(
                                                'answers',
                                                form.data.answers.map(
                                                    (answer, index) =>
                                                        index === questionIndex
                                                            ? event.target.value
                                                            : answer,
                                                ),
                                            )
                                        }
                                    />
                                </label>
                            ) : (
                                question.options?.map((option, optionIndex) => (
                                    <label
                                        key={optionIndex}
                                        className="flex items-start gap-3 rounded-md border p-3"
                                    >
                                        <input
                                            required
                                            type="radio"
                                            name={`question-${questionIndex}`}
                                            value={optionIndex}
                                            checked={
                                                attempt
                                                    ? Number(
                                                          attempt.answers[
                                                              questionIndex
                                                          ],
                                                      ) === optionIndex
                                                    : form.data.answers[
                                                          questionIndex
                                                      ] === String(optionIndex)
                                            }
                                            onChange={(event) =>
                                                form.setData(
                                                    'answers',
                                                    form.data.answers.map(
                                                        (answer, index) =>
                                                            index ===
                                                            questionIndex
                                                                ? event.target
                                                                      .value
                                                                : answer,
                                                    ),
                                                )
                                            }
                                            className="mt-1"
                                        />
                                        <span className="break-words">
                                            {option}
                                            {isTeacher &&
                                                question.correct ===
                                                    optionIndex &&
                                                ' ✓ Correcta'}
                                        </span>
                                    </label>
                                ))
                            )}
                        </fieldset>
                    ))}
                    {Object.entries(form.errors).map(([key, error]) => (
                        <p
                            key={key}
                            role="alert"
                            className="text-destructive text-sm"
                        >
                            {error}
                        </p>
                    ))}
                    {!isTeacher && !attempt && !isExpired && (
                        <>
                            <p className="text-muted-foreground text-sm">
                                Al enviar se guardan tus respuestas definitivas.
                                Las preguntas abiertas serán revisadas por el
                                profesor.
                            </p>
                            <Button disabled={form.processing}>
                                Enviar evaluación
                            </Button>
                        </>
                    )}
                </form>
                {results && (
                    <section className="flex flex-col gap-4">
                        <h2 className="font-semibold">Resultados y revisión</h2>
                        {results.data.map((result) => (
                            <Review
                                key={result.id}
                                evaluationId={evaluation.id}
                                attempt={result}
                                questions={questions}
                            />
                        ))}
                        {!results.data.length && (
                            <p className="text-muted-foreground">
                                Aún no hay respuestas.
                            </p>
                        )}
                        <div className="flex gap-4">
                            {results.prev_page_url && (
                                <Link href={results.prev_page_url}>
                                    Anterior
                                </Link>
                            )}
                            {results.next_page_url && (
                                <Link href={results.next_page_url}>
                                    Siguiente
                                </Link>
                            )}
                        </div>
                    </section>
                )}
            </main>
        </>
    );
}
Evaluation.layout = { breadcrumbs: [{ title: 'Evaluaciones', href: index() }] };
