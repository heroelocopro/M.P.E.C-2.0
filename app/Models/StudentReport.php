<?php

namespace App\Models;

use Database\Factories\StudentReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'reported_by_id', 'category', 'title', 'details', 'reported_at'])]
class StudentReport extends Model
{
    /** @use HasFactory<StudentReportFactory> */
    use HasFactory;

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['reported_at' => 'datetime'];
    }
}
