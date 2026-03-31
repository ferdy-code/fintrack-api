<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['sometimes', 'string', 'size:3', 'exists:currencies,code'],
            'period' => ['required', 'in: weekly,monthly, yearly'],
            'alert_threshold' => ['sometimes', 'numeric', 'between:0.5,1.0'],
            'start_date' => ['required', 'date'],
        ];
    }
}
