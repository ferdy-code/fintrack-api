<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ExportService
{
    public function exportTransactionsCsv(User $user, array $filters)
    {
        $transactions = $this->getFilteredTransactions($user, $filters);
        $filename = 'transactions_'.Carbon::now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://temp', 'r+');
            fputcsv($file, ['Date', 'Type', 'Category', 'Description', 'Amount', 'Currency', 'Wallet', 'Merchant', 'Notes']);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->transaction_date->format('Y-m-d'),
                    $tx->type,
                    $tx->category ? $tx->category->name : '',
                    $tx->description ?? '',
                    $tx->amount,
                    $tx->currency_code,
                    $tx->wallet ? $tx->wallet->name : '',
                    $tx->merchant_name ?? '',
                    $tx->notes ?? '',
                ]);
            }

            rewind($file);
            fpassthru($file);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportTransactionsPdf(User $user, array $filters)
    {
        $transactions = $this->getFilteredTransactions($user, $filters);
        $rows = $this->prepareTransactionRows($transactions);
        $month = Carbon::now()->format('F Y');
        $pdf = Pdf::loadView('exports.transactions', compact('rows', 'user', 'month'));
        $filename = 'transactions_'.Carbon::now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function exportMonthlyReportPdf(User $user, int $year, int $month)
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();
        $prevMonthStart = $monthStart->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $monthStart->copy()->subMonth()->endOfMonth();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->with(['wallet.currency', 'category'])
            ->orderBy('transaction_date')
            ->get();

        $totalIncome = (float) $transactions->where('type', TransactionType::Income->value)->sum('amount');
        $totalExpense = (float) $transactions->where('type', TransactionType::Expense->value)->sum('amount');
        $net = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? round((($totalIncome - $totalExpense) / $totalIncome) * 100, 2) : 0;

        $categoryBreakdown = $transactions
            ->where('type', TransactionType::Expense->value)
            ->filter(fn ($item) => $item->category !== null)
            ->groupBy(fn ($item) => $item->category_id)
            ->map(function ($group) use ($totalExpense) {
                $total = $group->sum('amount');

                return [
                    'name' => $group->first()->category->name,
                    'total' => (float) $total,
                    'percentage' => $totalExpense > 0 ? round(($total / $totalExpense) * 100, 2) : 0,
                ];
            })->sortByDesc('total')->values()->toArray();

        $topMerchants = $transactions
            ->where('type', TransactionType::Expense->value)
            ->whereNotNull('merchant_name')
            ->groupBy('merchant_name')
            ->map(fn ($group) => [
                'name' => $group->first()->merchant_name,
                'total' => (float) $group->sum('amount'),
            ])->sortByDesc('total')->take(5)->values()->toArray();

        $prevIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Income->value)
            ->whereBetween('transaction_date', [$prevMonthStart, $prevMonthEnd])
            ->sum('amount');
        $prevExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('transaction_date', [$prevMonthStart, $prevMonthEnd])
            ->sum('amount');

        $rows = $transactions->map(fn ($tx) => [
            'date' => $tx->transaction_date->format('Y-m-d'),
            'type' => ucfirst($tx->type),
            'category' => $tx->category ? $tx->category->name : '-',
            'description' => $tx->description ?? '-',
            'amount' => number_format((float) $tx->amount, 2),
            'wallet' => $tx->wallet ? $tx->wallet->name : '-',
        ])->values()->toArray();

        $data = [
            'user' => $user,
            'month' => Carbon::create($year, $month, 1)->format('F Y'),
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net' => $net,
            'savings_rate' => $savingsRate,
            'category_breakdown' => $categoryBreakdown,
            'top_merchants' => $topMerchants,
            'transaction_count' => $transactions->count(),
            'prev_income' => $prevIncome,
            'prev_expense' => $prevExpense,
            'income_change' => $prevIncome > 0 ? round((($totalIncome - $prevIncome) / $prevIncome) * 100, 2) : 0,
            'expense_change' => $prevExpense > 0 ? round((($totalExpense - $prevExpense) / $prevExpense) * 100, 2) : 0,
            'transactions' => $rows,
        ];

        $pdf = Pdf::loadView('exports.monthly-report', $data);
        $filename = 'monthly_report_'.$monthStart->format('Y-m').'.pdf';

        return $pdf->download($filename);
    }

    private function getFilteredTransactions(User $user, array $filters)
    {
        $query = Transaction::where('user_id', $user->id)
            ->with(['wallet.currency', 'category'])
            ->orderBy('transaction_date', 'desc');

        if (! empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }
        if (! empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->get();
    }

    private function prepareTransactionRows($transactions): array
    {
        return $transactions->map(fn ($tx) => [
            'date' => $tx->transaction_date->format('Y-m-d'),
            'type' => ucfirst($tx->type),
            'category' => $tx->category ? $tx->category->name : '-',
            'description' => $tx->description ?? '-',
            'amount' => number_format((float) $tx->amount, 2),
            'currency' => $tx->currency_code,
            'wallet' => $tx->wallet ? $tx->wallet->name : '-',
            'merchant' => $tx->merchant_name ?? '-',
            'notes' => $tx->notes ?? '-',
        ])->all();
    }
}
