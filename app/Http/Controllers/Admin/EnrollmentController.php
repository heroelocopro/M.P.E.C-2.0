<?php

namespace App\Http\Controllers\Admin;

use App\Actions\EnrollStudent;
use App\Enums\DocumentType;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;
use App\Services\InstitutionalAccountService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(EnrollmentStatus::class)],
            'year' => ['nullable', 'integer', 'exists:academic_years,id'],
        ]);
        $search = trim($validated['search'] ?? '');

        $enrollments = Enrollment::query()
            ->with(['student.user', 'academicYear', 'grade', 'classroom.campus', 'classroom.schoolShift'])
            ->when($search !== '', fn ($query) => $query->whereHas('student', fn ($studentQuery) => $studentQuery
                ->where('document_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($userQuery) => $userQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))))
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['year']), fn ($query) => $query->where('academic_year_id', $validated['year']))
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->id,
                'number' => $enrollment->enrollment_number,
                'student' => $enrollment->student->user?->name,
                'document' => $enrollment->student->document_number,
                'email' => $enrollment->student->user?->personal_email ?? $enrollment->student->user?->email,
                'year' => $enrollment->academicYear->name,
                'grade' => $enrollment->grade->name,
                'campus' => $enrollment->classroom?->campus->name,
                'shift' => $enrollment->classroom?->schoolShift->name,
                'status' => $enrollment->status->value,
                'enrolled_at' => $enrollment->enrolled_at->toDateString(),
            ]);

        return Inertia::render('admin/enrollments/index', [
            'enrollments' => $enrollments,
            'years' => AcademicYear::query()->orderByDesc('name')->get(['id', 'name']),
            'filters' => ['search' => $search, 'status' => $validated['status'] ?? null, 'year' => isset($validated['year']) ? (int) $validated['year'] : null],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/enrollments/create', [
            'years' => AcademicYear::query()->orderByDesc('name')->get(['id', 'name', 'is_active']),
            'grades' => Grade::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'classrooms' => Classroom::query()->with(['campus', 'schoolShift', 'grade', 'academicYear'])->where('is_active', true)->get()->map(fn (Classroom $classroom): array => [
                'id' => $classroom->id,
                'label' => "{$classroom->campus->name} · {$classroom->schoolShift->name} · {$classroom->grade->name} {$classroom->name} · {$classroom->academicYear->name}",
            ]),
            'documentTypes' => collect(DocumentType::cases())->map(fn (DocumentType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
            'suggestedNumber' => 'MAT-'.now()->format('Ymd-His'),
        ]);
    }

    public function store(StoreEnrollmentRequest $request, EnrollStudent $enrollStudent, InstitutionalAccountService $accounts): RedirectResponse
    {
        try {
            $classroom = Classroom::query()->with(['academicYear', 'grade'])->findOrFail($request->integer('classroom_id'));
            $guardianEmail = $request->string('guardian_email')->lower()->toString();
            $existingGuardian = User::query()->where('personal_email', $guardianEmail)->orWhere('email', $guardianEmail)->first();
            if ($existingGuardian !== null && $existingGuardian->role !== UserRole::Guardian) {
                throw new DomainException('El correo del acudiente pertenece a otro tipo de usuario.');
            }
            $enrollment = $enrollStudent->handle(
                [
                    'enrollment_number' => $request->string('enrollment_number')->toString(),
                    'document_type' => $request->string('document_type')->toString(),
                    'document_number' => $request->string('document_number')->toString(),
                    'first_name' => $request->string('first_name')->toString(),
                    'middle_name' => $request->string('middle_name')->toString() ?: null,
                    'last_name' => $request->string('last_name')->toString(),
                    'second_last_name' => $request->string('second_last_name')->toString() ?: null,
                    'birth_date' => $request->string('birth_date')->toString(),
                    'gender' => $request->string('gender')->toString() ?: null,
                    'phone' => $request->string('phone')->toString() ?: null,
                    'address' => $request->string('address')->toString() ?: null,
                    'email' => $request->string('email')->toString(),
                    'enrolled_at' => $request->string('enrolled_at')->toString(),
                    'observations' => $request->string('observations')->toString() ?: null,
                    'classroom_id' => $classroom->id,
                ],
                $classroom->academicYear,
                $classroom->grade,
                $accounts,
            );

            $guardian = $existingGuardian;
            if ($guardian === null) {
                $guardian = User::query()->create([
                    'name' => $request->string('guardian_name')->toString(),
                    'email' => 'pending+'.uniqid().'@'.config('app.institution_domain'),
                    'password' => 'pending',
                ]);
                $guardian->forceFill(['role' => UserRole::Guardian])->save();
                $accounts->provision($guardian, $guardianEmail);
            }
            $enrollment->student->guardians()->syncWithoutDetaching([
                $guardian->id => ['relationship' => $request->string('guardian_relationship')->toString()],
            ]);
        } catch (DomainException $exception) {
            return back()->withErrors(['enrollment' => $exception->getMessage()])->withInput();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'La matrícula fue creada correctamente.']);

        return to_route('admin.enrollments');
    }
}
