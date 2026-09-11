<?php

namespace App\Models;

use Database\Factories\GradingScaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['minimum', 'maximum', 'passing'])]
class GradingScale extends Model
{
    /** @use HasFactory<GradingScaleFactory> */
    use HasFactory;

    public static function current(): self
    {
        return self::query()->findOrFail(1);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['minimum' => 'float', 'maximum' => 'float', 'passing' => 'float'];
    }

    public function fromPercentage(float $percentage): float
    {
        return round($this->minimum + ($this->maximum - $this->minimum) * $percentage / 100, 2);
    }

    public function toPercentage(float $score): float
    {
        return 100 * ($score - $this->minimum) / ($this->maximum - $this->minimum);
    }

    /** @return array{minimum: float, maximum: float, passing: float} */
    public function limits(): array
    {
        return ['minimum' => $this->minimum, 'maximum' => $this->maximum, 'passing' => $this->passing];
    }

    /** @return array<string, mixed> */
    public function presentAttempt(EvaluationAttempt $attempt): array
    {
        return [
            ...$attempt->toArray(),
            'score' => $this->fromPercentage($attempt->score),
            'manual_scores' => $attempt->manual_scores === null ? null : collect($attempt->manual_scores)
                ->map(fn ($score): float => $this->fromPercentage((float) $score))->all(),
        ];
    }
}
