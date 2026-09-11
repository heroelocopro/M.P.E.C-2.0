<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Teacher
            && $this->user()->teacherAssignments()->whereKey($this->integer('teacher_assignment_id'))->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teacher_assignment_id' => ['required', 'integer'],
            'academic_period_id' => ['nullable', 'integer', function (string $attribute, mixed $value, \Closure $fail): void {
                $assignment = TeacherAssignment::query()->find($this->integer('teacher_assignment_id'));
                if (! $assignment || ! AcademicPeriod::query()->whereKey($value)->where('academic_year_id', $assignment->classroom->academic_year_id)->exists()) {
                    $fail('Selecciona un periodo del año lectivo del curso.');
                }
                if (AcademicPeriod::query()->whereKey($value)->where('is_closed', true)->exists()) {
                    $fail('No puedes agregar evaluaciones a un periodo cerrado.');
                }
            }],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'due_at' => ['required', 'date', 'after:now'],
            'questions' => ['required', 'array', 'list', 'min:1', 'max:50'],
            'questions.*' => ['required', 'array:type,prompt,options,correct'],
            'questions.*.type' => ['required', 'in:choice,open'],
            'questions.*.prompt' => ['required', 'string', 'max:1000'],
            'questions.*.options' => ['exclude_if:questions.*.type,open', 'required', 'array', 'list', 'size:4'],
            'questions.*.options.*' => ['exclude_if:questions.*.type,open', 'required', 'string', 'max:500'],
            'questions.*.correct' => ['exclude_if:questions.*.type,open', 'required', 'integer', 'between:0,3'],
        ];
    }
}
