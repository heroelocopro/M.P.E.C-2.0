<?php

namespace App\Http\Requests\Teacher;

use App\Enums\UserRole;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $this->user()?->role === UserRole::Teacher
            && $student instanceof Student
            && $this->user()->teacherAssignments()->whereHas('classroom.enrollments', fn ($query) => $query->where('student_id', $student->id)->where('status', 'activa'))->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'in:bienestar,academico,convivencia,salud'],
            'title' => ['required', 'string', 'max:160'],
            'details' => ['required', 'string', 'max:3000'],
        ];
    }
}
