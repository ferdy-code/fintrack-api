<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CurrencyService
{
    public function updateExchangeRates(): int
    {
        $response = Http::timeout(30)->get('https://api.frankfurter.app/latest', [
            'from' => 'USD',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch exchange rates: '.$response->status());
        }

        $rates = $response->json('rates', []);
        $updated = 0;

        foreach ($rates as $code => $rate) {
            $currency = Currency::find($code);

            if ($currency) {
                $currency->update(['exchange_rate_to_usd' => round(1 / $rate, 10)]);
                $updated++;
            }
        }

        Currency::where('code', 'USD')->update(['exchange_rate_to_usd' => 1.0]);

        return $updated;
    }

    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $from = Currency::find($fromCurrency);
        $to = Currency::find($toCurrency);

        if (! $from || ! $to) {
            throw new RuntimeException("Currency not found: {$fromCurrency} or {$toCurrency}");
        }

        $amountUsd = $amount * (float) $from->exchange_rate_to_usd;

        return round($amountUsd / (float) $to->exchange_rate_to_usd, 2);
    }
}
