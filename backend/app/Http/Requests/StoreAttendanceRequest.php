<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->where('is_active', true)],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'amount_paid' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'payment_method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'card', 'insurance', 'other'])],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'attendance_date' => ['required', 'date'],
            'procedures' => ['required', 'array', 'min:1'],
            'procedures.*' => ['required', 'integer', 'distinct', Rule::exists('procedures', 'id')->where('is_active', true)],
            'commission_percentage' => ['prohibited'],
            'commission_amount' => ['prohibited'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ((float) $this->input('amount_paid', 0) > 0 && ! $this->filled('payment_method')) {
                    $validator->errors()->add(
                        'payment_method',
                        'Informe o método do pagamento inicial.'
                    );
                }
            },
        ];
    }
}
