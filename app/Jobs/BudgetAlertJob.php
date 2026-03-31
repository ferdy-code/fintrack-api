<?php

namespace App\Jobs;

use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BudgetAlertJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Transaction $transaction) {}

    public function handle(): void
    {
        if (! $this->transaction->category_id) {
            return;
        }

        $category = $this->transaction->category;

        $budgets = Budget::where('category_id', $category->id)
            ->where('is_active', true)
            ->get();

        foreach ($budgets as $budget) {
            $spent = Transaction::where('user_id', $budget->user_id)
                ->where('category_id', $budget->category_id)
                ->where('transaction_date', '>=', $budget->start_date)
                ->when($budget->end_date, fn ($q) => $q->where('transaction_date', '<=', $budget->end_date))
                ->sum('amount');

            $percentage = $budget->amount > 0 ? ($spent / $budget->amount) * 100 : 0;

            if ($percentage >= ($budget->alert_threshold * 100)) {
                Log::info('Budget threshold exceeded', [
                    'budget_id' => $budget->id,
                    'category' => $category->name,
                    'spent' => $spent,
                    'budget' => $budget->amount,
                    'percentage' => round($percentage, 2),
                ]);
            }
        }
    }
}
