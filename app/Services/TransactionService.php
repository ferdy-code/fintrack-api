<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function createTransaction(array $data, User $user): Transaction
    {
        return DB::transaction(function () use ($data, $user) {
            $wallet = Wallet::where('id', $data['wallet_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $currencyCode = $wallet->currency_code;
            $type = $data['type'] ?? TransactionType::Income->value;

            if ($type === TransactionType::Transfer->value) {
                return $this->handleTransfer($data, $user, $wallet, $currencyCode);
            }

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $data['category_id'] ?? null,
                'type' => $type,
                'amount' => $data['amount'],
                'currency_code' => $currencyCode,
                'description' => $data['description'] ?? null,
                'merchant_name' => $data['merchant_name'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'is_recurring' => false,
                'ai_categorized' => false,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($type === TransactionType::Income->value) {
                $wallet->increment('balance', $data['amount']);
            } elseif ($type === TransactionType::Expense->value) {
                $wallet->decrement('balance', $data['amount']);
            }

            return $transaction;
        });
    }

    private function handleTransfer(array $data, User $user, Wallet $wallet, string $currencyCode): Transaction
    {
        $destinationWallet = Wallet::where('id', $data['destination_wallet_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $transferOutCategory = Category::where('is_system', true)
            ->where('type', 'expense')
            ->where('name', 'Transfer Out')
            ->first();
        $transferInCategory = Category::where('is_system', true)
            ->where('type', 'income')
            ->where('name', 'Transfer In')
            ->first();

        $expenseTx = Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $transferOutCategory->id,
            'type' => TransactionType::Expense->value,
            'amount' => $data['amount'],
            'currency_code' => $currencyCode,
            'description' => ($data['description'] ?? 'Transfer to '.$destinationWallet->name),
            'transaction_date' => $data['transaction_date'],
            'is_recurring' => false,
            'ai_categorized' => false,
            'notes' => null,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $destinationWallet->id,
            'category_id' => $transferInCategory->id,
            'type' => TransactionType::Income->value,
            'amount' => $data['amount'],
            'currency_code' => $currencyCode,
            'description' => ($data['description'] ?? 'Transfer from '.$wallet->name),
            'transaction_date' => $data['transaction_date'],
            'is_recurring' => false,
            'ai_categorized' => false,
            'notes' => null,
        ]);

        $wallet->decrement('balance', $data['amount']);
        $destinationWallet->increment('balance', $data['amount']);

        return $expenseTx;
    }

    public function updateTransaction(Transaction $tx, array $data): Transaction
    {
        return DB::transaction(function () use ($tx, $data) {
            $this->reverseBalanceImpact($tx->wallet, $tx->type, (float) $tx->amount);

            $tx->update($data);
            $tx->refresh();

            $this->applyBalanceImpact($tx->wallet, $tx->type, (float) $tx->amount);

            return $tx;
        });
    }

    public function deleteTransaction(Transaction $tx): void
    {
        DB::transaction(function () use ($tx) {
            $this->reverseBalanceImpact($tx->wallet, $tx->type, (float) $tx->amount);
            $tx->delete();
        });
    }

    public function getSummary(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $incomeByCategory = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Income->value)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get();

        $expenseByCategory = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get();

        $byCategory = $incomeByCategory->map(fn ($item) => [
            'category_id' => $item->category_id,
            'type' => 'income',
            'total' => (float) $item->total,
        ])->concat($expenseByCategory->map(fn ($item) => [
            'category_id' => $item->category_id,
            'type' => 'expense',
            'total' => (float) $item->total,
        ]))->values()->all();

        return [
            'total_income' => (float) $incomeByCategory->sum('total'),
            'total_expense' => (float) $expenseByCategory->sum('total'),
            'by_category' => $byCategory,
        ];
    }

    private function applyBalanceImpact(Wallet $wallet, string $type, float $amount): void
    {
        if ($type === TransactionType::Income->value) {
            $wallet->increment('balance', $amount);
        } elseif ($type === TransactionType::Expense->value) {
            $wallet->decrement('balance', $amount);
        }
    }

    private function reverseBalanceImpact(Wallet $wallet, string $type, float $amount): void
    {
        if ($type === TransactionType::Income->value) {
            $wallet->decrement('balance', $amount);
        } elseif ($type === TransactionType::Expense->value) {
            $wallet->increment('balance', $amount);
        }
    }
}
