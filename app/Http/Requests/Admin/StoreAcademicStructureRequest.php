<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicStructureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Administrator;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return match ($this->route('type')) {
            'campuses' => ['name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:20', Rule::unique('campuses')->ignore($id)], 'address' => ['nullable', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30']],
            'shifts' => ['name' => ['required', 'string', 'max:80'], 'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at']],
            'grades' => ['name' => ['required', 'string', 'max:80'], 'code' => ['required', 'string', 'max:20', Rule::unique('grades')->ignore($id)], 'sort_order' => ['required', 'integer', 'min:0', 'max:100'], 'is_final' => ['boolean']],
            'subjects' => ['name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:20', Rule::unique('subjects')->ignore($id)]],
            'classrooms' => ['name' => ['required', 'string', 'max:80'], 'campus_id' => ['required', 'exists:campuses,id'], 'school_shift_id' => ['required', 'exists:school_shifts,id'], 'grade_id' => ['required', 'exists:grades,id'], 'academic_year_id' => ['required', 'exists:academic_years,id'], 'capacity' => ['required', 'integer', 'min:1', 'max:100']],
            'periods' => ['academic_year_id' => ['required', 'exists:academic_years,id'], 'name' => ['required', 'string', 'max:80'], 'number' => ['required', 'integer', 'min:1', 'max:12'], 'starts_at' => ['required', 'date', 'before:ends_at'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'is_closed' => ['boolean']],
            default => [],
        };
    }
}
