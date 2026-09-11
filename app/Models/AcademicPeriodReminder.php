<?php

namespace App\Models;

use Database\Factories\AcademicPeriodReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['academic_period_id', 'days_before', 'sent_at'])]
class AcademicPeriodReminder extends Model
{
    /** @use HasFactory<AcademicPeriodReminderFactory> */
    use HasFactory;

    /** @return BelongsTo<AcademicPeriod, $this> */
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    protected function casts(): array
    {
        return ['days_before' => 'integer', 'sent_at' => 'datetime'];
    }
}
