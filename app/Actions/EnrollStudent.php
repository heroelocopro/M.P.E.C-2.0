<?php

namespace App\Actions;

use App\Enums\DocumentType;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use App\Services\InstitutionalAccountService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollStudent
{
    /**
     * @param  array{enrollment_number: string, document_type: DocumentType|string, document_number: string, first_name: string, middle_name?: string|null, last_name: string, second_last_name?: string|null, birth_date: string, gender?: string|null, phone?: string|null, address?: string|null, email: string, enrolled_at?: string, observations?: string|null, classroom_id?: int|null}  $data
     */
    public function handle(array $data, AcademicYear $academicYear, Grade $grade, ?InstitutionalAccountService $accounts = null): Enrollment
    {
        $accounts ??= app(InstitutionalAccountService::class);

        return DB::transaction(function () use ($data, $academicYear, $grade, $accounts): Enrollment {
            $documentType = $data['document_type'] instanceof DocumentType
                ? $data['document_type']
                : DocumentType::from($data['document_type']);

            $student = Student::query()
                ->where('document_type', $documentType->value)
                ->where('document_number', $data['document_number'])
                ->first();

            if ($student?->status === StudentStatus::Graduated) {
                throw new DomainException('Un estudiante egresado no puede recibir una nueva matrícula.');
            }

            $fullName = Str::squish(implode(' ', array_filter([
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name'],
                $data['second_last_name'] ?? null,
            ])));
            $email = Str::lower($data['email']);

            if ($student === null) {
                $user = User::query()->where('email', $email)->first();

                if ($user !== null && ($user->role !== UserRole::Student || $user->student()->exists())) {
                    throw new DomainException('El correo ya pertenece a otro usuario de la plataforma.');
                }

                $isNewUser = $user === null;
                $user ??= User::query()->create([
                    'name' => $fullName,
                    'email' => 'pending+'.uniqid().'@'.config('app.institution_domain'),
                    'password' => 'pending',
                ]);

                $user->forceFill(['role' => UserRole::Student])->save();
                if ($isNewUser) {
                    $accounts->provision($user, $email);
                }

                $student = Student::query()->create([
                    'user_id' => $user->id,
                    'document_type' => $documentType,
                    'document_number' => $data['document_number'],
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?? null,
                    'last_name' => $data['last_name'],
                    'second_last_name' => $data['second_last_name'] ?? null,
                    'birth_date' => $data['birth_date'],
                    'gender' => $data['gender'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'status' => StudentStatus::Active,
                ]);
            } else {
                $student->user?->update(['name' => $fullName, 'personal_email' => $email]);
                $student->update([
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?? null,
                    'last_name' => $data['last_name'],
                    'second_last_name' => $data['second_last_name'] ?? null,
                    'birth_date' => $data['birth_date'],
                    'gender' => $data['gender'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'status' => StudentStatus::Active,
                ]);
            }

            if ($student->enrollments()->whereBelongsTo($academicYear)->exists()) {
                throw new DomainException('El estudiante ya tiene una matrícula para este año lectivo.');
            }

            return $student->enrollments()->create([
                'enrollment_number' => $data['enrollment_number'],
                'academic_year_id' => $academicYear->id,
                'grade_id' => $grade->id,
                'classroom_id' => $data['classroom_id'] ?? null,
                'enrolled_at' => $data['enrolled_at'] ?? now()->toDateString(),
                'status' => EnrollmentStatus::Active,
                'observations' => $data['observations'] ?? null,
            ]);
        });
    }
}
