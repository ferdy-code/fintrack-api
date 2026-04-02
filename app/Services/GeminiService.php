<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    private string $apiKey;

    private string $model;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model');
        $this->baseUrl = config('services.gemini.base_url');
    }

    public function categorize(array $transactionData, Collection $categories): array
    {
        $merchantName = $transactionData['merchant_name'] ?? null;

        if ($merchantName) {
            $cacheKey = 'categorize:'.md5($merchantName);
            $cached = Cache::get($cacheKey);

            if ($cached) {
                return $cached;
            }
        }

        $categoryList = $categories->map(fn ($cat) => "ID: {$cat->id}, Name: {$cat->name}, Type: {$cat->type}")->implode("\n");

        $description = $transactionData['description'] ?? '';
        $merchant = $merchantName ?? 'N/A';
        $amount = $transactionData['amount'];
        $type = $transactionData['type'];

        $prompt = <<<PROMPT
You are a transaction categorization assistant. Given a transaction, select the most appropriate category from the list below.

Transaction:
- Description: {$description}
- Merchant: {$merchant}
- Amount: {$amount}
- Type: {$type}

Available Categories:
{$categoryList}

Respond with ONLY a JSON object:
{"category_id": <id>, "category_name": "<name>", "confidence": <0.0-1.0>}

If no category fits well, set category_id to null and confidence to 0.
PROMPT;

        $result = $this->callGemini($prompt);

        if ($result === null) {
            return ['category_id' => null, 'confidence' => 0];
        }

        $parsed = $this->parseJsonResponse($result);

        if ($parsed === null) {
            return ['category_id' => null, 'confidence' => 0];
        }

        $output = [
            'category_id' => $parsed['category_id'] ?? null,
            'category_name' => $parsed['category_name'] ?? null,
            'confidence' => (float) ($parsed['confidence'] ?? 0),
        ];

        if ($merchantName) {
            Cache::put('categorize:'.md5($merchantName), $output, 86400);
        }

        return $output;
    }

    public function getInsights(User $user): array
    {
        $cacheKey = "insights:{$user->id}";
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $now = Carbon::now();
        $last30Start = $now->copy()->subDays(30)->startOfDay();
        $prev30Start = $now->copy()->subDays(60)->startOfDay();

        $last30Transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$last30Start, $now])
            ->selectRaw('type, category_id, SUM(amount) as total')
            ->groupBy('type', 'category_id')
            ->get();

        $last30Income = (float) $last30Transactions->where('type', TransactionType::Income->value)->sum('total');
        $last30Expense = (float) $last30Transactions->where('type', TransactionType::Expense->value)->sum('total');

        $prev30Transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$prev30Start, $last30Start])
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->get();

        $prev30Income = (float) $prev30Transactions->where('type', TransactionType::Income->value)->sum('total');
        $prev30Expense = (float) $prev30Transactions->where('type', TransactionType::Expense->value)->sum('total');

        $categoryBreakdown = $last30Transactions
            ->where('type', TransactionType::Expense->value)
            ->map(function ($item) {
                $category = Category::find($item->category_id);

                return ['category' => $category?->name ?? 'Unknown', 'total' => (float) $item->total];
            })
            ->values()
            ->toArray();

        $budgetService = app(BudgetService::class);
        $budgets = $user->budgets()->where('is_active', true)->with('category')->get();
        $budgetUtilization = $budgets->map(function ($budget) use ($budgetService) {
            $spent = $budgetService->calculateSpending($budget);

            return [
                'name' => $budget->name ?? $budget->category?->name ?? 'Budget',
                'amount' => (float) $budget->amount,
                'spent' => $spent,
                'percentage' => $budget->amount > 0 ? round(($spent / $budget->amount) * 100, 1) : 0,
            ];
        })->toArray();

        $recurringTotal = $user->recurringTransactions()
            ->where('is_active', true)
            ->where('type', TransactionType::Expense->value)
            ->sum('amount');

        $net = $last30Income - $last30Expense;
        $savingsRate = $last30Income > 0 ? round((($last30Income - $last30Expense) / $last30Income) * 100, 1) : 0;
        $categoryBreakdownStr = $this->formatArray($categoryBreakdown);
        $budgetUtilizationStr = $this->formatArray($budgetUtilization);

        $prompt = <<<PROMPT
You are a personal finance advisor. Analyze the following financial data and provide 3-5 actionable insights.

User's Financial Summary (Last 30 days):
- Income: {$last30Income}
- Expense: {$last30Expense}
- Net: {$net}
- Savings Rate: {$savingsRate}%

Previous 30 days comparison:
- Income: {$prev30Income}
- Expense: {$prev30Expense}

Top Expense Categories:
{$categoryBreakdownStr}

Active Budgets:
{$budgetUtilizationStr}

Monthly Recurring Expenses: {$recurringTotal}

Respond with ONLY a JSON array of insights:
[
  {
    "title": "Short title",
    "description": "Detailed explanation with specific numbers",
    "potential_savings": <number or null>,
    "priority": "high|medium|low",
    "category": "spending|saving|budget|income|recurring"
  }
]
PROMPT;

        $result = $this->callGemini($prompt);

        if ($result === null) {
            return [];
        }

        $insights = $this->parseJsonResponse($result);

        if ($insights === null) {
            return [];
        }

        $insights = is_array($insights) && array_is_list($insights) ? $insights : [$insights];

        Cache::put($cacheKey, $insights, 21600);

        return $insights;
    }

    public function chatWithCallback(string $message, array $history, array $financialContext, callable $onChunk): string
    {
        $systemInstruction = $this->buildSystemInstruction($financialContext);
        $contents = $this->buildChatContents($history, $message);

        $url = "{$this->baseUrl}/models/{$this->model}:streamGenerateContent?key={$this->apiKey}&alt=sse";

        $payload = json_encode([
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
            ],
        ]);

        $fullResponse = '';
        $buffer = '';

        $onData = function ($ch, $data) use ($onChunk, &$fullResponse, &$buffer) {
            $buffer .= $data;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                if (! str_starts_with($line, 'data: ')) {
                    continue;
                }

                $json = json_decode(substr($line, 6), true);

                if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                    $text = $json['candidates'][0]['content']['parts'][0]['text'];
                    $fullResponse .= $text;
                    $onChunk($text);
                }
            }

            return strlen($data);
        };

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_WRITEFUNCTION => $onData,
        ]);

        curl_exec($ch);
        curl_close($ch);

        return $fullResponse;
    }

    private function callGemini(string $prompt): ?string
    {
        try {
            $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30)
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json('candidates.0.content.parts.0.text');
        } catch (ConnectionException) {
            return null;
        }
    }

    private function parseJsonResponse(?string $response): ?array
    {
        if ($response === null) {
            return null;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $decoded = json_decode($matches[1], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    private function buildSystemInstruction(array $context): string
    {
        $topCategories = $this->formatArray($context['top_categories'] ?? []);
        $budgets = $this->formatArray($context['budgets'] ?? []);
        $walletBalances = $this->formatArray($context['wallet_balances'] ?? []);
        $monthlyIncome = $context['monthly_income'];
        $monthlyExpense = $context['monthly_expense'];
        $totalBalance = $context['total_balance'];

        return <<<PROMPT
You are FinTrack AI, a personal finance assistant. You help users understand their spending, manage budgets, and make better financial decisions.

User's Financial Context:
- Monthly Income: {$monthlyIncome}
- Monthly Expense: {$monthlyExpense}
- Total Balance: {$totalBalance}
- Top Categories: {$topCategories}
- Active Budgets: {$budgets}
- Wallet Balances: {$walletBalances}

Guidelines:
- Be concise and actionable
- Reference specific numbers from the user's data when relevant
- Suggest practical steps
- If asked about non-finance topics, politely redirect to finance
- Always respond in the same language the user uses
PROMPT;
    }

    private function buildChatContents(array $history, string $newMessage): array
    {
        $contents = [];

        foreach ($history as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'model' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $newMessage]],
        ];

        return $contents;
    }

    private function formatArray(array $data): string
    {
        if (empty($data)) {
            return 'None';
        }

        return collect($data)->map(fn ($item) => '- '.json_encode($item))->implode("\n");
    }
}
