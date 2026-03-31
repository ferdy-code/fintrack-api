<?php

namespace App\Observers;

use App\Jobs\BudgetAlertJob;
use App\Models\Transaction;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        $this->dispatchBudgetAlert($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        $this->dispatchBudgetAlert($transaction);
    }

    public function deleted(Transaction $transaction): void
    {
        if ($transaction->category_id) {
            BudgetAlertJob::dispatch($transaction);
        }
    }

    private function dispatchBudgetAlert(Transaction $transaction): void
    {
        if ($transaction->category_id) {
            BudgetAlertJob::dispatch($transaction);
        }
    }
}
