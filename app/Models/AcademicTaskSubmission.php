<?php

namespace App\Models;

use Database\Factories\AcademicTaskSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property Carbon $submitted_at
 */
#[Fillable(['academic_task_id', 'enrollment_id', 'disk', 'path', 'original_name', 'mime_type', 'size', 'submitted_at', 'feedback'])]
class AcademicTaskSubmission extends Model
{
    /** @use HasFactory<AcademicTaskSubmissionFactory> */
    use HasFactory;

    /** @return BelongsTo<AcademicTask, $this> */
    public function academicTask(): BelongsTo
    {
        return $this->belongsTo(AcademicTask::class);
    }

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['size' => 'integer', 'submitted_at' => 'datetime'];
    }
}
