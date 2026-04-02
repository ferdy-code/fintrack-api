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
You are a financial transaction categorizer. Given a transaction description
and merchant name, classify it into one of the provided categories.
Always respond in Bahasa Indonesia.

Transaction:
- Description: "{$description}"
- Merchant: "{$merchant}"
- Amount: {$amount}
- Type: {$type}

Available categories:
{$categoryList}

Respond in JSON format only:
{
    "category_id": <id>,
    "category_name": "<name>",
    "confidence": <0.0-1.0>
}        
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

        $period = 30;
        $now = Carbon::now();
        $last30Start = $now->copy()->subDays($period)->startOfDay();
        $prev30Start = $now->copy()->subDays($period * 2)->startOfDay();

        $last30Transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$last30Start, $now])
            ->selectRaw('type, category_id, SUM(amount) as total')
            ->groupBy('type', 'category_id')
            ->get();

        $total_income = (float) $last30Transactions->where('type', TransactionType::Income->value)->sum('total');
        $total_expenses = (float) $last30Transactions->where('type', TransactionType::Expense->value)->sum('total');

        $prev30Transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$prev30Start, $last30Start])
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->get();

        $prev30Income = (float) $prev30Transactions->where('type', TransactionType::Income->value)->sum('total');
        $prev30Expense = (float) $prev30Transactions->where('type', TransactionType::Expense->value)->sum('total');

        $currency = $user->wallets()->where('is_active', true)->first()?->currency_code ?? 'USD';

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

        $recurringExpenses = $user->recurringTransactions()
            ->where('is_active', true)
            ->where('type', TransactionType::Expense->value)
            ->get();

        $savings_rate = $total_income > 0 ? round((($total_income - $total_expenses) / $total_income) * 100, 1) : 0;
        $category_breakdown = $this->formatArray($categoryBreakdown);
        $budget_status = $this->formatArray($budgetUtilization);
        $recurring_list = $recurringExpenses->isEmpty()
            ? 'None'
            : $recurringExpenses->map(function ($r) use ($currency) {
                $freq = $r->frequency ?? 'N/A';

                return "- {$r->description}: {$r->amount} {$currency} ({$freq})";
            })->implode("\n");

        $incomeChange = $prev30Income > 0 ? round((($total_income - $prev30Income) / $prev30Income) * 100, 1) : 0;
        $expenseChange = $prev30Expense > 0 ? round((($total_expenses - $prev30Expense) / $prev30Expense) * 100, 1) : 0;
        $comparison = "Previous period income: {$prev30Income} {$currency} (change: ".($incomeChange >= 0 ? '+' : '')."{$incomeChange}%)\n";
        $comparison .= "Previous period expenses: {$prev30Expense} {$currency} (change: ".($expenseChange >= 0 ? '+' : '')."{$expenseChange}%)";

        $prompt = <<<PROMPT
You are a personal financial advisor. Analyze the user's spending data and provide
actionable savings suggestions. Be specific with numbers and percentages.
Always respond in Bahasa Indonesia.

User's Financial Data (Last {$period} days):
- Total Income: {$total_income} {$currency}
- Total Expenses: {$total_expenses} {$currency}
- Savings Rate: {$savings_rate}%

Spending by Category:
{$category_breakdown}

Budget Status:
{$budget_status}

Recurring Expenses:
{$recurring_list}

Previous Period Comparison:
{$comparison}

Provide 3-5 specific, actionable insights in this JSON format:
{
    "insights": [
        {
            "title": "Short title",
            "description": "Detailed explanation with specific numbers",
            "potential_savings": <amount>,
            "priority": "high|medium|low",
            "category": "related category name"
        }
    ],
    "overall_health_score": <1-100>,
    "summary": "One paragraph overall assessment"
}
PROMPT;

        $result = $this->callGemini($prompt);

        if ($result === null) {
            return [];
        }

        $parsed = $this->parseJsonResponse($result);

        if ($parsed === null) {
            return [];
        }

        Cache::put($cacheKey, $parsed, 21600);

        return $parsed;
    }

    public function chatStream(string $message, array $history, array $financialContext): array
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
        $chunks = [];
        $buffer = '';

        $onData = function ($ch, $data) use (&$fullResponse, &$chunks, &$buffer) {
            $buffer .= $data;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = rtrim(substr($buffer, 0, $pos), "\r");
                $buffer = substr($buffer, $pos + 1);

                if (! str_starts_with($line, 'data: ')) {
                    continue;
                }

                $json = json_decode(substr($line, 6), true);

                if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                    $text = $json['candidates'][0]['content']['parts'][0]['text'];
                    $fullResponse .= $text;
                    $chunks[] = $text;
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

        return ['response' => $fullResponse, 'chunks' => $chunks];
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
        $currency = $context['currency'] ?? 'USD';
        $monthly_income = $context['monthly_income'];
        $monthly_expenses = $context['monthly_expense'];
        $budgets_summary = $this->formatArray($context['budgets'] ?? []);
        $top_categories = $this->formatArray($context['top_categories'] ?? []);
        $wallet_balances = $this->formatArray($context['wallet_balances'] ?? []);
        $recent_transactions = $this->formatArray($context['recent_transactions'] ?? []);

        return <<<PROMPT
You are FinTrack AI, a friendly and knowledgeable personal financial advisor.
You have access to the user's financial data summarized below.
Always reference specific numbers from their data when giving advice.
Be encouraging but honest. Use the user's currency ({$currency}) for amounts.
Always respond in Bahasa Indonesia (Indonesian language).
If asked about something outside personal finance, politely redirect.

User's Financial Summary:
- Monthly income: {$monthly_income}
- Monthly expenses: {$monthly_expenses}
- Active budgets: {$budgets_summary}
- Top spending categories: {$top_categories}
- Wallet balances: {$wallet_balances}
- Recent transactions: {$recent_transactions}
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
