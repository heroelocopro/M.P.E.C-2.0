<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ProcessAcademicClosures;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicStructureRequest;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicStructureController extends Controller
{
    public function closePeriod(AcademicPeriod $period, ProcessAcademicClosures $closures): RedirectResponse
    {
        abort_unless($period->academicYear()->where('is_active', true)->exists(), 422);

        if ($period->is_closed) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'El periodo ya estaba cerrado.']);

            return back();
        }

        $closures->closePeriod($period);
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$period->name} fue cerrado y sus notas finales quedaron consolidadas."]);

        return back();
    }

    public function index(): Response
    {
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        return Inertia::render('admin/academic-settings', [
            'campuses' => Campus::query()->orderBy('name')->get(),
            'shifts' => SchoolShift::query()->orderBy('starts_at')->get(),
            'grades' => Grade::query()->orderBy('sort_order')->get(),
            'subjects' => Subject::query()->orderBy('name')->get(),
            'years' => AcademicYear::query()->orderByDesc('name')->get(['id', 'name']),
            'periods' => AcademicPeriod::query()->with('academicYear')->where('academic_year_id', $activeYear?->id)->orderBy('number')->get()->map(fn (AcademicPeriod $period): array => [
                'id' => $period->id,
                'name' => $period->name,
                'number' => $period->number,
                'academic_year_id' => $period->academic_year_id,
                'year' => $period->academicYear->name,
                'starts_at' => $period->starts_at->format('Y-m-d'),
                'ends_at' => $period->ends_at->format('Y-m-d'),
                'is_closed' => $period->is_closed,
            ]),
            'classrooms' => Classroom::query()->with(['campus', 'schoolShift', 'grade', 'academicYear'])->withCount('enrollments')->orderBy('name')->get()->map(fn (Classroom $classroom): array => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'capacity' => $classroom->capacity,
                'campus_id' => $classroom->campus_id,
                'school_shift_id' => $classroom->school_shift_id,
                'grade_id' => $classroom->grade_id,
                'academic_year_id' => $classroom->academic_year_id,
                'campus' => $classroom->campus->name,
                'shift' => $classroom->schoolShift->name,
                'grade' => $classroom->grade->name,
                'year' => $classroom->academicYear->name,
                'students' => $classroom->enrollments_count,
            ]),
        ]);
    }

    public function store(StoreAcademicStructureRequest $request, string $type): RedirectResponse
    {
        try {
            $this->model($type)::query()->create($request->validated());
        } catch (QueryException) {
            return back()->withErrors(['structure' => 'Ya existe una configuración con esa combinación de datos.'])->withInput();
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => 'La configuración fue creada correctamente.']);

        return back();
    }

    public function update(StoreAcademicStructureRequest $request, string $type, int $id): RedirectResponse
    {
        try {
            $this->find($type, $id)->update($request->validated());
        } catch (QueryException) {
            return back()->withErrors(['structure' => 'Ya existe una configuración con esa combinación de datos.'])->withInput();
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Los cambios fueron guardados.']);

        return back();
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        try {
            $this->find($type, $id)->delete();
        } catch (QueryException) {
            return back()->withErrors(['structure' => 'No se puede eliminar porque ya tiene información académica relacionada.']);
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => 'El registro fue eliminado.']);

        return back();
    }

    /** @return class-string<Model> */
    private function model(string $type): string
    {
        return match ($type) {
            'campuses' => Campus::class,
            'shifts' => SchoolShift::class,
            'grades' => Grade::class,
            'subjects' => Subject::class,
            'classrooms' => Classroom::class,
            'periods' => AcademicPeriod::class,
            default => abort(404)
        };
    }

    private function find(string $type, int $id): Model
    {
        return $this->model($type)::query()->findOrFail($id);
    }
}
