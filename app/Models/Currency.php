<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'symbol', 'decimal_places', 'exchange_rate_to_usd'])]
class Currency extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    public function getCreatedAtColumn(): ?string
    {
        return null;
    }

    public function getUpdatedAtColumn(): ?string
    {
        return 'updated_at';
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'currency_code', 'code');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'currency_code', 'code');
    }
}
