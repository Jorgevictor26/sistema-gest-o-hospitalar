<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'procedure' => ['required', 'string', 'max:255', 'unique:procedures,procedure'],
            'price' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ];
    }
}
