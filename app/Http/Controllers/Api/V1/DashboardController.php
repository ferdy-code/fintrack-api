<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $budgetService = app(BudgetService::class);
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $totalBalance = Wallet::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('currency')
            ->get()
            ->sum(fn($wallet) => (float) $wallet->balance);

        $totalIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Income->value)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $totalExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $savingsRate = $totalIncome > 0 ? round((($totalIncome - $totalExpense) / $totalIncome) * 100, 2) : 0;

        $categoryBreakdown = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->with('category')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->filter(fn($item) => $item->category !== null)
            ->map(fn($item) => [
                'category' => new CategoryResource($item->category),
                'total' => (float) $item->total,
                'percentage' => $totalExpense > 0 ? round(((float) $item->total / $totalExpense) * 100, 2) : 0,
            ]);

        $budgetAlerts = Budget::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->filter(fn($budget) => $budgetService->checkBudgetAlert($budget))
            ->values();

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['wallet.currency', 'category'])
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get();

        $monthlyTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $tMonth = Carbon::now()->subMonths($i);
            $tStart = $tMonth->copy()->startOfMonth();
            $tEnd = $tMonth->copy()->endOfMonth();

            $income = (float) Transaction::where('user_id', $user->id)
                ->where('type', TransactionType::Income->value)
                ->whereBetween('transaction_date', [$tStart, $tEnd])
                ->sum('amount');

            $expense = (float) Transaction::where('user_id', $user->id)
                ->where('type', TransactionType::Expense->value)
                ->whereBetween('transaction_date', [$tStart, $tEnd])
                ->sum('amount');

            $monthlyTrend->push([
                'month' => $tMonth->format('M Y'),
                'income' => $income,
                'expense' => $expense,
            ]);
        }

        $walletBalances = Wallet::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('currency')
            ->get();

        return $this->successResponse([
            'total_balance' => $totalBalance,
            'month_summary' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'savings_rate' => $savingsRate,
            ],
            'category_breakdown' => $categoryBreakdown,
            'budget_alerts' => BudgetResource::collection($budgetAlerts),
            'recent_transactions' => TransactionResource::collection($recentTransactions),
            'monthly_trend' => $monthlyTrend,
            'wallet_balances' => WalletResource::collection($walletBalances),
        ]);
    }
}
