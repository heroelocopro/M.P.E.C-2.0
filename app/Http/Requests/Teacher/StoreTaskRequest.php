<?php

namespace App\Http\Requests\Teacher;

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->role === UserRole::Teacher
            && TeacherAssignment::query()
                ->whereBelongsTo($user, 'teacher')
                ->where('classroom_id', $this->integer('classroom_id'))
                ->where('subject_id', $this->integer('subject_id'))
                ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'academic_period_id' => ['sometimes', 'integer', function (string $attribute, mixed $value, \Closure $fail): void {
                $period = AcademicPeriod::query()->whereKey($value)->first();
                $classroom = Classroom::query()->find($this->integer('classroom_id'));

                if (! $period || ! $classroom || $period->academic_year_id !== $classroom->academic_year_id) {
                    $fail('Selecciona un periodo del año lectivo del curso.');
                }
            }],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'type' => ['required', 'string', 'in:tarea,proyecto'],
            'due_at' => ['required', 'date', 'after:now'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp,zip', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp,zip', 'max:10240'],
        ];
    }
}
