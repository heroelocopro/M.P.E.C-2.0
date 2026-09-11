<?php

namespace App\Actions;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use DomainException;
use Illuminate\Support\Facades\DB;

class CompleteEnrollment
{
    public function handle(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment): Enrollment {
            $lockedEnrollment = Enrollment::query()
                ->with(['grade', 'student'])
                ->lockForUpdate()
                ->findOrFail($enrollment->id);

            if ($lockedEnrollment->status !== EnrollmentStatus::Active) {
                throw new DomainException('Solo una matrícula activa puede completarse.');
            }

            $lockedEnrollment->update([
                'status' => EnrollmentStatus::Completed,
                'completed_at' => now(),
            ]);

            if ($lockedEnrollment->grade->is_final) {
                $lockedEnrollment->student->update([
                    'status' => StudentStatus::Graduated,
                    'graduated_at' => today(),
                ]);
            }

            return $lockedEnrollment->refresh();
        });
    }
}
