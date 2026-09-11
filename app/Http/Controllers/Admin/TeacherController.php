<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherAssignmentRequest;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\InstitutionalAccountService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/teachers/index', [
            'teachers' => User::query()->where('role', UserRole::Teacher)->with(['teacherAssignments.classroom.campus', 'teacherAssignments.classroom.schoolShift', 'teacherAssignments.classroom.grade', 'teacherAssignments.subject'])->orderBy('name')->get()->map(fn(User $teacher): array => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'personal_email' => $teacher->personal_email,
                'assignments' => $teacher->teacherAssignments->map(fn(TeacherAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'subject' => $assignment->subject->name,
                    'classroom' => $assignment->classroom->name,
                    'campus' => $assignment->classroom->campus->name,
                    'shift' => $assignment->classroom->schoolShift->name,
                    'grade' => $assignment->classroom->grade->name,
                ]),
            ]),
            'classrooms' => Classroom::query()->with(['campus', 'schoolShift', 'grade', 'academicYear'])->where('is_active', true)->get()->map(fn(Classroom $classroom): array => [
                'id' => $classroom->id,
                'label' => "{$classroom->campus->name} · {$classroom->schoolShift->name} · {$classroom->grade->name} {$classroom->name} · {$classroom->academicYear->name}",
            ]),
            'subjects' => Subject::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTeacherRequest $request, InstitutionalAccountService $accounts): RedirectResponse
    {
        $data = $request->validated();
        $teacher = User::query()->create(['name' => $data['name'], 'email' => 'pending+' . uniqid() . '@' . config('app.institution_domain'), 'password' => 'pending']);
        $teacher->forceFill(['role' => UserRole::Teacher])->save();
        $accounts->provision($teacher, $data['email']);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profesor creado. Sus credenciales institucionales fueron enviadas al correo personal.']);

        return back();
    }

    public function assign(StoreTeacherAssignmentRequest $request): RedirectResponse
    {
        TeacherAssignment::query()->firstOrCreate($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asignación académica guardada.']);

        return back();
    }

    public function unassign(TeacherAssignment $teacherAssignment): RedirectResponse
    {
        $teacherAssignment->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asignación eliminada.']);

        return back();
    }
}
