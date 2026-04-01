<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecurringTransactionResource;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $recurring = $request->user()->recurringTransactions()
            ->with(['wallet', 'category'])
            ->get();

        return $this->successResponse(RecurringTransactionResource::collection($recurring));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => ['required', 'exists:wallets,id'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'frequency' => ['required', 'in:daily,weekly,biweekly,monthly,quarterly,yearly'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'merchant_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'next_due_date' => ['required', 'date'],
            'auto_create' => ['sometimes', 'boolean'],
        ]);

        $recurring = RecurringTransaction::create(array_merge($validated, [
            'user_id' => $request->user()->id,
        ]));

        $recurring->load(['wallet', 'category']);

        return $this->successResponse(new RecurringTransactionResource($recurring), 'Recurring transaction created successfully', 201);
    }

    public function show(Request $request, RecurringTransaction $recurringTransaction): JsonResponse
    {
        $this->authorizeRecurring($recurringTransaction, $request->user()->id);

        return $this->successResponse(new RecurringTransactionResource($recurringTransaction->load(['wallet', 'category'])));
    }

    public function update(Request $request, RecurringTransaction $recurringTransaction): JsonResponse
    {
        $this->authorizeRecurring($recurringTransaction, $request->user()->id);

        $validated = $request->validate([
            'wallet_id' => ['sometimes', 'exists:wallets,id'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'type' => ['sometimes', 'in:income,expense'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'frequency' => ['sometimes', 'in:daily,weekly,biweekly,monthly,quarterly,yearly'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'merchant_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'next_due_date' => ['sometimes', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'auto_create' => ['sometimes', 'boolean'],
        ]);

        $recurringTransaction->update($validated);

        return $this->successResponse(new RecurringTransactionResource($recurringTransaction->load(['wallet', 'category'])), 'Recurring transaction updated successfully');
    }

    public function destroy(Request $request, RecurringTransaction $recurringTransaction): JsonResponse
    {
        $this->authorizeRecurring($recurringTransaction, $request->user()->id);

        $recurringTransaction->delete();

        return $this->successResponse(null, 'Recurring transaction deleted successfully');
    }

    public function skip(Request $request, RecurringTransaction $recurringTransaction): JsonResponse
    {
        $this->authorizeRecurring($recurringTransaction, $request->user()->id);

        $nextDueDate = $this->advanceNextDueDate($recurringTransaction->frequency, Carbon::parse($recurringTransaction->next_due_date));
        $recurringTransaction->update(['next_due_date' => $nextDueDate]);

        return $this->successResponse(new RecurringTransactionResource($recurringTransaction->load(['wallet', 'category'])), 'Skipped to next due date');
    }

    public function processNow(Request $request, RecurringTransaction $recurringTransaction): JsonResponse
    {
        $this->authorizeRecurring($recurringTransaction, $request->user()->id);

        $wallet = Wallet::findOrFail($recurringTransaction->wallet_id);

        $transaction = Transaction::create([
            'user_id' => $recurringTransaction->user_id,
            'wallet_id' => $recurringTransaction->wallet_id,
            'category_id' => $recurringTransaction->category_id,
            'type' => $recurringTransaction->type,
            'amount' => $recurringTransaction->amount,
            'currency_code' => $recurringTransaction->currency_code,
            'description' => $recurringTransaction->description,
            'merchant_name' => $recurringTransaction->merchant_name,
            'transaction_date' => now(),
            'is_recurring' => true,
            'recurring_id' => $recurringTransaction->id,
            'ai_categorized' => false,
            'notes' => null,
        ]);

        if ($recurringTransaction->type === TransactionType::Income->value) {
            $wallet->increment('balance', $recurringTransaction->amount);
        } else {
            $wallet->decrement('balance', $recurringTransaction->amount);
        }

        $nextDueDate = $this->advanceNextDueDate($recurringTransaction->frequency, Carbon::parse($recurringTransaction->next_due_date));
        $recurringTransaction->update([
            'next_due_date' => $nextDueDate,
            'last_processed' => now()->toDateString(),
        ]);

        return $this->successResponse(new RecurringTransactionResource($recurringTransaction->load(['wallet', 'category'])), 'Recurring transaction processed');
    }

    private function authorizeRecurring(RecurringTransaction $recurring, int $userId): void
    {
        if ($recurring->user_id !== $userId) {
            abort(403);
        }
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
