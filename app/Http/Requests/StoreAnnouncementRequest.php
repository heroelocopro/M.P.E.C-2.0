<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user?->role === UserRole::Administrator) {
            return $this->string('audience')->toString() === 'institutional';
        }

        if ($user?->role !== UserRole::Teacher || ! in_array($this->string('audience')->toString(), ['all_classes', 'classroom'], true)) {
            return false;
        }

        return $this->string('audience')->toString() === 'all_classes'
            ? $user->teacherAssignments()->exists()
            : TeacherAssignment::query()
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
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:5000'],
            'audience' => ['required', 'string', Rule::in(['institutional', 'all_classes', 'classroom'])],
            'classroom_id' => ['nullable', 'required_if:audience,classroom', 'integer', 'exists:classrooms,id'],
        ];
    }
}
