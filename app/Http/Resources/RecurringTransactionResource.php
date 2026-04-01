<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'currency' => [
                'code' => $this->currency_code,
            ],
            'description' => $this->description,
            'merchant_name' => $this->merchant_name,
            'frequency' => $this->frequency,
            'next_due_date' => $this->next_due_date?->toDateString(),
            'last_processed' => $this->last_processed?->toDateString(),
            'is_active' => $this->is_active,
            'auto_create' => $this->auto_create,
            'created_at' => $this->created_at,
        ];
    }
}
