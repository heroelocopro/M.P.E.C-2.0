<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentType;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
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
        return [
            'enrollment_number' => ['required', 'string', 'max:50', 'unique:enrollments,enrollment_number'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document_number' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'second_last_name' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'guardian_name' => ['required', 'string', 'max:160'],
            'guardian_email' => ['required', 'email', 'max:255', 'different:email'],
            'guardian_relationship' => ['required', 'string', 'max:80'],
            'enrolled_at' => ['required', 'date'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
