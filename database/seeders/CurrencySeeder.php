<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimal_places' => 0, 'exchange_rate_to_usd' => 0.0000645161, 'updated_at' => now()],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'exchange_rate_to_usd' => 1.0000000000, 'updated_at' => now()],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => "\u{20AC}", 'decimal_places' => 2, 'exchange_rate_to_usd' => 1.0869565217, 'updated_at' => now()],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => "\u{00A3}", 'decimal_places' => 2, 'exchange_rate_to_usd' => 1.2658227848, 'updated_at' => now()],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => "\u{00A5}", 'decimal_places' => 0, 'exchange_rate_to_usd' => 0.0066666667, 'updated_at' => now()],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimal_places' => 2, 'exchange_rate_to_usd' => 0.7462686567, 'updated_at' => now()],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimal_places' => 2, 'exchange_rate_to_usd' => 0.2237136465, 'updated_at' => now()],
        ];

        DB::table('currencies')->insert($currencies);
    }
}
