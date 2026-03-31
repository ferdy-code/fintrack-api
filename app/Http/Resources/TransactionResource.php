<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => [
                'code' => $this->currency_code,
                'symbol' => $this->currency->symbol,
            ],
            'description' => $this->description,
            'merchant_name' => $this->merchant_name,
            'transaction_date' => $this->transaction_date,
            'wallet' => new WalletResource($this->wallet),
            'category' => new CategoryResource($this->category),
            'ai_categorized' => $this->ai_categorized,
            'ai_confidence' => $this->ai_confidence,
            'notes' => $this->notes,
            'is_recurring' => $this->is_recurring,
            'created_at' => $this->created_at,
        ];
    }
}
