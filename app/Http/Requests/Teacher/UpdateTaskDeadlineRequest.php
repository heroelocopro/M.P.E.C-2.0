<?php

namespace App\Http\Requests\Teacher;

use App\Models\AcademicTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskDeadlineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('academicTask');

        return $task instanceof AcademicTask
            && $task->type !== 'evaluacion'
            && $task->assigned_by_id === $this->user()?->id
            && $this->user()->teacherAssignments()
                ->where('classroom_id', $task->classroom_id)
                ->where('subject_id', $task->subject_id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'due_at' => ['required', 'date', 'after:now'],
        ];
    }
}
