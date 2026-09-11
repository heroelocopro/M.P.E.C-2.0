<?php

namespace App\Http\Requests\Teacher;

use App\Enums\UserRole;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
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
            'date' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*' => ['required', 'string', 'in:presente,ausente,tarde,excusa'],
            'notes' => ['sometimes', 'array'],
            'notes.*' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
