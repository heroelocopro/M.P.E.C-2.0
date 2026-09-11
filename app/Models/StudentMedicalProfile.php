<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'blood_type', 'allergies', 'medical_conditions', 'disabilities', 'health_provider', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship', 'gender_identity', 'sexual_orientation', 'ethnic_identity', 'indigenous_people', 'data_consent'])]
class StudentMedicalProfile extends Model
{
    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['data_consent' => 'boolean'];
    }
}
