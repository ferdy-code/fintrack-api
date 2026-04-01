<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'wallet_id', 'category_id', 'type', 'amount', 'currency_code', 'frequency', 'description', 'merchant_name', 'next_due_date', 'last_processed', 'is_active', 'auto_create'])]
class RecurringTransaction extends Model
{
    protected $attributes = [
        'is_active' => true,
        'auto_create' => true,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_due_date' => 'date',
            'last_processed' => 'date',
            'is_active' => 'boolean',
            'auto_create' => 'boolean',
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

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }
}
