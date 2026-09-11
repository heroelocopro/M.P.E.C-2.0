<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGradingScaleRequest extends FormRequest
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
            'minimum' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.98'],
            'maximum' => ['required', 'numeric', 'decimal:0,2', 'gt:minimum', 'max:999999.99'],
            'passing' => ['required', 'numeric', 'decimal:0,2', 'gte:minimum', 'lte:maximum'],
        ];
    }
}
