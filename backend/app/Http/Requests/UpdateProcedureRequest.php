<?php

namespace App\Http\Requests;

use App\Models\Procedure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProcedureRequest extends FormRequest
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
        /** @var Procedure $procedure */
        $procedure = $this->route('procedure');

        return [
            'procedure' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('procedures', 'procedure')->ignore($procedure),
            ],
            'price' => ['sometimes', 'required', 'numeric', 'gt:0', 'decimal:0,2'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny(['procedure', 'price'])) {
                    $validator->errors()->add(
                        'procedure',
                        'Informe o nome ou o preço que pretende actualizar.'
                    );
                }
            },
        ];
    }
}
