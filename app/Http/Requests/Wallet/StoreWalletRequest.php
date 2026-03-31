<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:bank,e-wallet,cash,credit-card'],
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'balance' => ['sometimes', 'numeric', 'min:0'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'color' => ['sometimes', 'nullable', 'string', 'max:7'],
        ];
    }
}
