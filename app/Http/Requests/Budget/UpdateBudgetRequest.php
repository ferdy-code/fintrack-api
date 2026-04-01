<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'alert_threshold' => ['sometimes', 'numeric', 'between:0.5,1.0'],
            'period' => ['sometimes', 'in:weekly,monthly,yearly'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
