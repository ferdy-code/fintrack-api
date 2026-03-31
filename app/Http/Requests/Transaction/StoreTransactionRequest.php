<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'exists:wallets,id'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'merchant_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('destination_wallet_id', 'required|exists:wallets,id', function ($input) {
            return ($input->type ?? $this->input('type')) === 'transfer';
        });
    }
}
