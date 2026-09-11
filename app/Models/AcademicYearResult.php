<?php

namespace App\Models;

use Database\Factories\AcademicYearResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_id', 'final_average', 'passed', 'outcome', 'subject_results', 'closed_at'])]
class AcademicYearResult extends Model
{
    /** @use HasFactory<AcademicYearResultFactory> */
    use HasFactory;

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    protected function casts(): array
    {
        return ['final_average' => 'float', 'passed' => 'boolean', 'subject_results' => 'array', 'closed_at' => 'datetime'];
    }
}
