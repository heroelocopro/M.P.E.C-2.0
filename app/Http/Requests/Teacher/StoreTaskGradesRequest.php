<?php

namespace App\Http\Requests\Teacher;

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\GradingScale;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskGradesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Teacher
            && TeacherAssignment::query()
                ->whereBelongsTo($this->user(), 'teacher')
                ->whereKey($this->integer('assignment_id'))
                ->exists()
            && ! AcademicPeriod::query()->whereKey($this->integer('period_id'))->where('is_closed', true)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $scale = GradingScale::current();

        return [
            'assignment_id' => ['required', 'integer', 'exists:teacher_assignments,id'],
            'period_id' => ['nullable', 'integer', 'exists:academic_periods,id'],
            'grades' => ['required', 'array', 'min:1'],
            'grades.*' => ['required', 'array', 'min:1'],
            'grades.*.*' => ['nullable', 'numeric', "between:{$scale->minimum},{$scale->maximum}"],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $scale = GradingScale::current();

        return [
            'grades.*.*.numeric' => 'La nota debe ser un número.',
            'grades.*.*.between' => 'La nota debe estar entre '.number_format($scale->minimum, 2, ',', '.').' y '.number_format($scale->maximum, 2, ',', '.').'.',
        ];
    }
}
