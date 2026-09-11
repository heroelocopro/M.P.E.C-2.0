<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $card_token
 * @property DocumentType $document_type
 * @property string $document_number
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $second_last_name
 * @property Carbon $birth_date
 * @property StudentStatus $status
 * @property Carbon|null $graduated_at
 * @property-read User|null $user
 * @property-read Collection<int, Enrollment> $enrollments
 * @property-read Collection<int, StudentReport> $reports
 */
#[Fillable(['user_id', 'card_token', 'document_type', 'document_number', 'first_name', 'middle_name', 'last_name', 'second_last_name', 'birth_date', 'gender', 'phone', 'address', 'status', 'graduated_at'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'guardian_student', 'student_id', 'guardian_id')->withPivot('relationship')->withTimestamps();
    }

    /** @return HasMany<StudentReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(StudentReport::class);
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasOne<Enrollment, $this> */
    public function latestEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)->ofMany('id', 'max');
    }

    /** @return HasOne<StudentMedicalProfile, $this> */
    public function medicalProfile(): HasOne
    {
        return $this->hasOne(StudentMedicalProfile::class);
    }

    /** @return HasMany<InstitutionalAttendance, $this> */
    public function institutionalAttendances(): HasMany
    {
        return $this->hasMany(InstitutionalAttendance::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['document_type' => DocumentType::class, 'birth_date' => 'date', 'status' => StudentStatus::class, 'graduated_at' => 'date'];
    }
}
