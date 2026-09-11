<?php

namespace App\Http\Requests\Student;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\AcademicTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('academicTask');

        return $this->user()?->role === UserRole::Student
            && $task instanceof AcademicTask
            && $task->type !== 'evaluacion'
            && $this->user()->student?->enrollments()
                ->where('classroom_id', $task->classroom_id)
                ->where('status', EnrollmentStatus::Active)
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
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'extensions:pdf,doc,docx,txt', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Selecciona un archivo para entregar.',
            'file.mimes' => 'Solo puedes subir archivos PDF, Word o TXT.',
            'file.extensions' => 'La extensión debe ser PDF, DOC, DOCX o TXT.',
            'file.max' => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
