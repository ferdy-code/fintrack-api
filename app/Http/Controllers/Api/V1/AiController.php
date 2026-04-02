<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiChatRequest;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\DB;

class AiController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    public function categorize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string'],
            'merchant_name' => ['sometimes', 'nullable', 'string'],
            'amount' => ['required', 'numeric'],
            'type' => ['required', 'in:income,expense'],
        ]);

        $merchantName = $validated['merchant_name'] ?? null;

        if ($merchantName) {
            $cached = cache('categorize:'.md5($merchantName));
            if ($cached) {
                return $this->successResponse($cached, 'Category suggestion (cached)');
            }
        }

        $categories = Category::forUser($request->user()->id)
            ->where('type', $validated['type'])
            ->get();

        $result = $this->gemini->categorize($validated, $categories);

        return $this->successResponse($result, 'Category suggestion');
    }

    public function insights(Request $request): JsonResponse
    {
        $insights = $this->gemini->getInsights($request->user());

        return $this->successResponse($insights, 'Financial insights');
    }

    public function chat(AiChatRequest $request)
    {
        $user = $request->user();
        $sessionId = $request->input('session_id');
        $message = $request->validated('message');

        if ($sessionId) {
            $session = AiChatSession::where('id', $sessionId)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            $session = AiChatSession::create([
                'user_id' => $user->id,
                'title' => str($message)->limit(100)->toString(),
            ]);
        }

        AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $message,
            'created_at' => now(),
        ]);

        $history = $session->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($msg) => ['role' => $msg->role, 'content' => $msg->content])
            ->toArray();

        $financialContext = $this->buildFinancialContext($user);
        $sessionId = $session->id;

        return response()->eventStream(function () use ($message, $history, $financialContext, $sessionId) {
            $result = $this->gemini->chatStream(
                $message,
                $history,
                $financialContext,
            );

            foreach ($result['chunks'] as $chunk) {
                yield new StreamedEvent(
                    event: 'chunk',
                    data: json_encode(['type' => 'chunk', 'content' => $chunk])
                );
            }

            AiChatMessage::create([
                'session_id' => $sessionId,
                'role' => 'model',
                'content' => $result['response'],
                'created_at' => now(),
            ]);

            yield new StreamedEvent(
                event: 'done',
                data: json_encode(['type' => 'done', 'session_id' => $sessionId])
            );
        });
    }

    public function chatSessions(Request $request): JsonResponse
    {
        $sessions = $request->user()->aiChatSessions()
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get();

        return $this->successResponse($sessions);
    }

    public function chatHistory(Request $request, int $sessionId): JsonResponse
    {
        $session = AiChatSession::where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $messages = $session->messages()->orderBy('created_at', 'asc')->get();

        return $this->successResponse([
            'session' => $session,
            'messages' => $messages,
        ]);
    }

    public function deleteChatSession(Request $request, int $sessionId): JsonResponse
    {
        $session = AiChatSession::where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $session->delete();

        return $this->successResponse(null, 'Chat session deleted successfully');
    }

    private function buildFinancialContext($user): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $monthlyIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Income->value)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $totalBalance = Wallet::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('balance');

        $topCategories = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense->value)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->with('category')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->filter(fn ($item) => $item->category !== null)
            ->map(fn ($item) => ['name' => $item->category->name, 'total' => (float) $item->total])
            ->values()
            ->toArray();

        $budgets = $user->budgets()
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->map(fn ($b) => [
                'name' => $b->category?->name ?? 'Budget',
                'amount' => (float) $b->amount,
            ])
            ->toArray();

        $walletBalances = Wallet::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('currency')
            ->get()
            ->map(fn ($w) => ['name' => $w->name, 'balance' => (float) $w->balance, 'currency' => $w->currency_code])
            ->toArray();

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['category', 'wallet'])
            ->orderByDesc('transaction_date')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'description' => $t->description,
                'amount' => (float) $t->amount,
                'type' => $t->type,
                'category' => $t->category?->name,
                'date' => $t->transaction_date->format('Y-m-d'),
            ])
            ->toArray();

        $currency = Wallet::where('user_id', $user->id)
            ->where('is_active', true)
            ->first()?->currency_code ?? 'USD';

        return [
            'monthly_income' => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'total_balance' => $totalBalance,
            'top_categories' => $topCategories,
            'budgets' => $budgets,
            'wallet_balances' => $walletBalances,
            'recent_transactions' => $recentTransactions,
            'currency' => $currency,
        ];
    }
}
