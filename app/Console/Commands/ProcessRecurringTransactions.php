<?php

namespace App\Console\Commands;

use App\Enums\TransactionType;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'recurring:process';

    protected $description = 'Process active recurring transactions that are due';

    public function handle(): int
    {
        $recurringTransactions = RecurringTransaction::where('is_active', true)
            ->where('auto_create', true)
            ->where('next_due_date', '<=', Carbon::today())
            ->get();

        $processed = 0;

        foreach ($recurringTransactions as $recurring) {
            $wallet = Wallet::find($recurring->wallet_id);

            if (! $wallet) {
                $this->warn("Wallet not found for recurring transaction ID: {$recurring->id}");

                continue;
            }

            Transaction::create([
                'user_id' => $recurring->user_id,
                'wallet_id' => $recurring->wallet_id,
                'category_id' => $recurring->category_id,
                'type' => $recurring->type,
                'amount' => $recurring->amount,
                'currency_code' => $recurring->currency_code,
                'description' => $recurring->description,
                'merchant_name' => $recurring->merchant_name,
                'transaction_date' => now(),
                'is_recurring' => true,
                'recurring_id' => $recurring->id,
                'ai_categorized' => false,
                'notes' => null,
            ]);

            if ($recurring->type === TransactionType::Income->value) {
                $wallet->increment('balance', $recurring->amount);
            } else {
                $wallet->decrement('balance', $recurring->amount);
            }

            $nextDueDate = $this->advanceNextDueDate($recurring->frequency, Carbon::parse($recurring->next_due_date));

            $recurring->update([
                'next_due_date' => $nextDueDate,
                'last_processed' => Carbon::today()->toDateString(),
            ]);

            $processed++;
        }

        $this->info("Processed {$processed} recurring transaction(s).");

        return self::SUCCESS;
    }

    private function advanceNextDueDate(string $frequency, Carbon $currentDate): string
    {
        return match ($frequency) {
            'daily' => $currentDate->addDay()->toDateString(),
            'weekly' => $currentDate->addWeek()->toDateString(),
            'biweekly' => $currentDate->addWeeks(2)->toDateString(),
            'monthly' => $currentDate->addMonth()->toDateString(),
            'quarterly' => $currentDate->addMonths(3)->toDateString(),
            'yearly' => $currentDate->addYear()->toDateString(),
            default => $currentDate->addMonth()->toDateString(),
        };
    }
}
