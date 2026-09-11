<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Evaluation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $evaluation = $this->route('evaluation');

        return $this->user()?->role === UserRole::Student
            && $evaluation instanceof Evaluation
            && $evaluation->teacherAssignment->classroom->accessibleTo($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'answers' => ['required', 'array', 'list', 'size:'.count($this->route('evaluation')->questions)],
        ];
        foreach ($this->route('evaluation')->questions as $index => $question) {
            $rules['answers.'.$index] = $question['type'] === 'open'
                ? ['required', 'string', 'max:5000']
                : ['required', 'integer', 'between:0,3'];
        }

        return $rules;
    }
}
