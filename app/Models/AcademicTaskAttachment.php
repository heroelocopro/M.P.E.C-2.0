<?php

namespace App\Models;

use Database\Factories\AcademicTaskAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['academic_task_id', 'disk', 'path', 'original_name', 'mime_type', 'size'])]
class AcademicTaskAttachment extends Model
{
    /** @use HasFactory<AcademicTaskAttachmentFactory> */
    use HasFactory;

    /** @return BelongsTo<AcademicTask, $this> */
    public function academicTask(): BelongsTo
    {
        return $this->belongsTo(AcademicTask::class);
    }
}
