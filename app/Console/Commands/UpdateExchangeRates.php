<?php

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    protected $signature = 'currency:update-rates';

    protected $description = 'Update currency exchange rates from frankfurter API';

    public function handle(): int
    {
        $count = app(CurrencyService::class)->updateExchangeRates();

        $this->info("Updated {$count} currency exchange rates.");

        return self::SUCCESS;
    }
}
