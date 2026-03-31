<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'category_id', 'amount', 'currency_code', 'period', 'alert_threshold', 'start_date', 'end_date', 'is_active'])]
class Budget extends Model
{
    protected $attributes = [
        'alert_threshold' => 0.80,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'alert_threshold' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
