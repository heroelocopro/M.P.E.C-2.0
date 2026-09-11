<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $date
 * @property Carbon $checked_in_at
 */
#[Fillable(['student_id', 'recorded_by_id', 'date', 'checked_in_at', 'method', 'notes'])]
class InstitutionalAttendance extends Model
{
    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['date' => 'date', 'checked_in_at' => 'datetime:H:i'];
    }
}
