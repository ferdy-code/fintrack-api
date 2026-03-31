<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'wallet_id', 'category_id', 'type', 'amount', 'currency_code', 'description', 'merchant_name', 'transaction_date', 'is_recurring', 'recurring_id', 'ai_categorized', 'ai_confidence', 'notes', 'attachment_url'])]
class Transaction extends Model
{
    protected $attributes = [
        'is_recurring' => false,
        'ai_categorized' => false,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'datetime',
            'is_recurring' => 'boolean',
            'ai_categorized' => 'boolean',
            'ai_confidence' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class, 'recurring_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }
}
