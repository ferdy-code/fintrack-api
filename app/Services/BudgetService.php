<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;

class BudgetService
{
    public function getCurrentPeriodDates(Budget $budget): array
    {
        $start = Carbon::parse($budget->start_date);

        return match ($budget->period) {
            'weekly' => [
                $start->copy()->startOfWeek(),
                $start->copy()->endOfWeek(),
            ],
            'monthly' => [
                $start->copy()->startOfMonth(),
                $start->copy()->endOfMonth(),
            ],
            'yearly' => [
                $start->copy()->startOfYear(),
                $start->copy()->endOfYear(),
            ],
            default => [
                $start->copy()->startOfMonth(),
                $start->copy()->endOfMonth(),
            ],
        };
    }

    public function calculateSpending(Budget $budget): float
    {
        [$periodStart, $periodEnd] = $this->getCurrentPeriodDates($budget);

        return (float) Transaction::where('user_id', $budget->user_id)
            ->where('category_id', $budget->category_id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');
    }

    public function checkBudgetAlert(Budget $budget): bool
    {
        $spent = $this->calculateSpending($budget);

        return $budget->amount > 0 && $spent >= ($budget->amount * $budget->alert_threshold);
    }

    public function getBudgetWithStats(Budget $budget): array
    {
        $spent = $this->calculateSpending($budget);
        [$periodStart, $periodEnd] = $this->getCurrentPeriodDates($budget);
        $remaining = max(0, (float) $budget->amount - $spent);
        $percentageUsed = $budget->amount > 0 ? round(($spent / $budget->amount) * 100, 2) : 0;
        $daysRemaining = (int) Carbon::now()->diffInDays($periodEnd, false);

        return [
            'spent' => $spent,
            'remaining' => $remaining,
            'percentage_used' => $percentageUsed,
            'is_over_budget' => $spent > (float) $budget->amount,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'days_remaining' => max(0, $daysRemaining),
        ];
    }
}
