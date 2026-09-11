import { usePage, Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, show, store } from '@/routes/evaluations';
import { useState } from 'react';

export default function Evaluations({
    isTeacher,
    assignments,
    periods,
    evaluations,
}: {
    isTeacher: boolean;
    assignments: { id: number; academic_year_id: number; label: string }[];
    periods: { id: number; academic_year_id: number; name: string }[];
    evaluations: {
        data: {
            id: number;
            title: string;
            teacher_assignment_id: number;
            due_at: string;
            attempts: { score: number; graded_at: string | null }[];
        }[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}) {
    const { gradingScale } = usePage().props;
    const [creating, setCreating] = useState(false);
    const form = useForm({
        teacher_assignment_id: assignments[0]?.id.toString() ?? '',
        academic_period_id: '',
        title: '',
        description: '',
        due_at: '',
        questions: [
            {
                type: 'choice',
                prompt: '',
                options: ['', '', '', ''],
                correct: 0,
            },
        ],
    });
    return (
        <>
            <Head title="Evaluaciones" />
            <main className="flex flex-col gap-6 p-4 sm:p-8">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-semibold">Evaluaciones</h1>
                        <p className="text-muted-foreground mt-2">
                            Preguntas de selección múltiple y abiertas. Escala
                            de {gradingScale.minimum} a {gradingScale.maximum}.
                        </p>
                    </div>
                    {isTeacher && (
                        <Button
                            disabled={!assignments.length}
                            onClick={() => setCreating(!creating)}
                        >
                            {creating
                                ? 'Cerrar formulario'
                                : 'Nueva evaluación'}
                        </Button>
                    )}
                </div>
                {creating && (
                    <form
                        className="bg-card flex flex-col gap-4 rounded-xl border p-5"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.transform((data) => ({
                                ...data,
                                due_at: data.due_at
                                    ? new Date(data.due_at).toISOString()
                                    : '',
                            }));
                            form.post(store.url());
                        }}
                    >
                        <label className="flex flex-col gap-2">
                            Materia y curso
                            <select
                                className="bg-background rounded-md border p-2"
                                value={form.data.teacher_assignment_id}
                                onChange={(event) =>
                                    form.setData(
                                        'teacher_assignment_id',
                                        event.target.value,
                                    )
                                }
                            >
                                {assignments.map((assignment) => (
                                    <option
                                        key={assignment.id}
                                        value={assignment.id}
                                    >
                                        {assignment.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="flex flex-col gap-2">
                            Periodo académico
                            <select
                                required
                                className="bg-background rounded-md border p-2"
                                value={form.data.academic_period_id}
                                onChange={(event) =>
                                    form.setData(
                                        'academic_period_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">Seleccionar periodo</option>
                                {periods
                                    .filter(
                                        (period) =>
                                            period.academic_year_id ===
                                            assignments.find(
                                                (assignment) =>
                                                    String(assignment.id) ===
                                                    form.data
                                                        .teacher_assignment_id,
                                            )?.academic_year_id,
                                    )
                                    .map((period) => (
                                        <option
                                            key={period.id}
                                            value={period.id}
                                        >
                                            {period.name}
                                        </option>
                                    ))}
                            </select>
                            {form.errors.academic_period_id && (
                                <span className="text-destructive text-sm">
                                    {form.errors.academic_period_id}
                                </span>
                            )}
                        </label>
                        <label className="flex flex-col gap-2">
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
                        <label className="flex flex-col gap-2">
                            Indicaciones
                            <textarea
                                className="bg-background rounded-md border p-3"
                                maxLength={3000}
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                            />
                        </label>
                        <label className="flex flex-col gap-2">
                            Fecha de vencimiento
                            <Input
                                required
                                type="datetime-local"
                                value={form.data.due_at}
                                onChange={(event) =>
                                    form.setData('due_at', event.target.value)
                                }
                            />
                        </label>
                        {form.data.questions.map((question, questionIndex) => (
                            <fieldset
                                key={questionIndex}
                                className="flex flex-col gap-3 rounded-lg border p-4"
                            >
                                <legend className="px-2 font-medium">
                                    Pregunta {questionIndex + 1}
                                </legend>
                                <label className="flex flex-col gap-2">
                                    Tipo de pregunta
                                    <select
                                        className="bg-background rounded-md border p-2"
                                        value={question.type}
                                        onChange={(event) =>
                                            form.setData(
                                                'questions',
                                                form.data.questions.map(
                                                    (item, index) =>
                                                        index === questionIndex
                                                            ? {
                                                                  ...item,
                                                                  type: event
                                                                      .target
                                                                      .value,
                                                              }
                                                            : item,
                                                ),
                                            )
                                        }
                                    >
                                        <option value="choice">
                                            Selección múltiple
                                        </option>
                                        <option value="open">
                                            Respuesta abierta
                                        </option>
                                    </select>
                                </label>
                                <label className="flex flex-col gap-2">
                                    Enunciado
                                    <Input
                                        required
                                        maxLength={1000}
                                        value={question.prompt}
                                        onChange={(event) =>
                                            form.setData(
                                                'questions',
                                                form.data.questions.map(
                                                    (item, index) =>
                                                        index === questionIndex
                                                            ? {
                                                                  ...item,
                                                                  prompt: event
                                                                      .target
                                                                      .value,
                                                              }
                                                            : item,
                                                ),
                                            )
                                        }
                                    />
                                </label>
                                {question.type === 'choice' &&
                                    question.options.map(
                                        (option, optionIndex) => (
                                            <label
                                                key={optionIndex}
                                                className="flex flex-col gap-2 text-sm"
                                            >
                                                Opción {optionIndex + 1}
                                                <Input
                                                    required
                                                    maxLength={500}
                                                    value={option}
                                                    onChange={(event) =>
                                                        form.setData(
                                                            'questions',
                                                            form.data.questions.map(
                                                                (
                                                                    item,
                                                                    index,
                                                                ) =>
                                                                    index ===
                                                                    questionIndex
                                                                        ? {
                                                                              ...item,
                                                                              options:
                                                                                  item.options.map(
                                                                                      (
                                                                                          value,
                                                                                          position,
                                                                                      ) =>
                                                                                          position ===
                                                                                          optionIndex
                                                                                              ? event
                                                                                                    .target
                                                                                                    .value
                                                                                              : value,
                                                                                  ),
                                                                          }
                                                                        : item,
                                                            ),
                                                        )
                                                    }
                                                />
                                            </label>
                                        ),
                                    )}
                                {question.type === 'choice' && (
                                    <label className="flex flex-col gap-2 text-sm">
                                        Respuesta correcta
                                        <select
                                            className="bg-background rounded-md border p-2"
                                            value={question.correct}
                                            onChange={(event) =>
                                                form.setData(
                                                    'questions',
                                                    form.data.questions.map(
                                                        (item, index) =>
                                                            index ===
                                                            questionIndex
                                                                ? {
                                                                      ...item,
                                                                      correct:
                                                                          Number(
                                                                              event
                                                                                  .target
                                                                                  .value,
                                                                          ),
                                                                  }
                                                                : item,
                                                    ),
                                                )
                                            }
                                        >
                                            {question.options.map(
                                                (_, index) => (
                                                    <option
                                                        key={index}
                                                        value={index}
                                                    >
                                                        Opción {index + 1}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                    </label>
                                )}
                                {form.data.questions.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            form.setData(
                                                'questions',
                                                form.data.questions.filter(
                                                    (_, index) =>
                                                        index !== questionIndex,
                                                ),
                                            )
                                        }
                                    >
                                        Quitar pregunta
                                    </Button>
                                )}
                            </fieldset>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            disabled={form.data.questions.length >= 50}
                            onClick={() =>
                                form.setData('questions', [
                                    ...form.data.questions,
                                    {
                                        type: 'choice',
                                        prompt: '',
                                        options: ['', '', '', ''],
                                        correct: 0,
                                    },
                                ])
                            }
                        >
                            Agregar pregunta
                        </Button>
                        {Object.entries(form.errors).map(([key, error]) => (
                            <p
                                key={key}
                                role="alert"
                                className="text-destructive text-sm"
                            >
                                {error}
                            </p>
                        ))}
                        <p className="text-muted-foreground text-sm">
                            Revisa las preguntas antes de publicar. Los
                            resultados se consultan aquí y no modifican
                            automáticamente el boletín.
                        </p>
                        <Button disabled={form.processing}>
                            Publicar evaluación
                        </Button>
                    </form>
                )}
                {evaluations.data.map((evaluation) => (
                    <Link
                        key={evaluation.id}
                        href={show(evaluation.id)}
                        className="bg-card rounded-xl border p-5 hover:border-indigo-400"
                    >
                        <h2 className="font-semibold">{evaluation.title}</h2>
                        <p className="text-muted-foreground mt-2 text-sm">
                            {
                                assignments.find(
                                    (assignment) =>
                                        assignment.id ===
                                        evaluation.teacher_assignment_id,
                                )?.label
                            }{' '}
                            · Vence{' '}
                            {new Date(evaluation.due_at).toLocaleString(
                                'es-CO',
                            )}
                        </p>
                        <p className="mt-2 text-sm font-medium">
                            {evaluation.attempts.length
                                ? evaluation.attempts[0].graded_at
                                    ? `Resultado: ${evaluation.attempts[0].score}/${gradingScale.maximum}`
                                    : 'Pendiente de revisión'
                                : new Date(evaluation.due_at) < new Date()
                                  ? 'Vencida'
                                  : 'Disponible'}
                        </p>
                    </Link>
                ))}
                {!evaluations.data.length && (
                    <p className="text-muted-foreground">
                        Todavía no hay evaluaciones publicadas.
                    </p>
                )}
                <div className="flex gap-4">
                    {evaluations.prev_page_url && (
                        <Link href={evaluations.prev_page_url}>Anterior</Link>
                    )}
                    {evaluations.next_page_url && (
                        <Link href={evaluations.next_page_url}>Siguiente</Link>
                    )}
                </div>
            </main>
        </>
    );
}
Evaluations.layout = {
    breadcrumbs: [{ title: 'Evaluaciones', href: index() }],
};
