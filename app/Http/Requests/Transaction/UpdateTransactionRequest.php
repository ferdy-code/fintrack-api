<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['sometimes', 'exists:wallets,id'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'type' => ['sometimes', 'in:income,expense,transfer'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'description' => ['sometimes', 'string', 'max:500'],
            'merchant_name' => ['sometimes', 'string', 'max:255'],
            'transaction_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('destination_wallet_id', 'required|exists:wallets,id', function ($input) {
            return ($input['type'] ?? $this->input('type')) === 'transfer';
        });
    }
}
