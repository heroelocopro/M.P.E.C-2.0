<?php

namespace App\Models;

use Database\Factories\ScheduleEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['classroom_id', 'subject_id', 'teacher_id', 'day_of_week', 'starts_at', 'ends_at', 'room'])]
class ScheduleEntry extends Model
{
    /** @use HasFactory<ScheduleEntryFactory> */
    use HasFactory;

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }
}
