<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Evaluation;
use App\Models\EvaluationAttempt;
use App\Models\GradingScale;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GradeEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $evaluation = $this->route('evaluation');
        $attempt = $this->route('attempt');

        return $this->user()?->role === UserRole::Teacher
            && $evaluation instanceof Evaluation
            && $attempt instanceof EvaluationAttempt
            && $attempt->evaluation_id === $evaluation->id
            && $evaluation->teacherAssignment->teacher_id === $this->user()->id
            && ! $evaluation->academicTask?->academicPeriod?->is_closed;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $scale = GradingScale::current();
        $indexes = collect($this->route('evaluation')->questions)->keys()->filter(fn ($index) => $this->route('evaluation')->questions[$index]['type'] === 'open')->all();
        $rules = [
            'manual_scores' => ['required', 'array:'.implode(',', $indexes)],
            'feedback' => ['nullable', 'string', 'max:3000'],
        ];
        foreach ($indexes as $index) {
            $rules['manual_scores.'.$index] = ['required', 'numeric', "between:{$scale->minimum},{$scale->maximum}"];
        }

        return $rules;
    }
}
