<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::where('user_id', $request->user()->id)
            ->with(['wallet.currency', 'category']);

        if ($request->filled('wallet_id')) {
            $query->where('wallet_id', $request->input('wallet_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->input('end_date'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', '%'.$search.'%')
                    ->orWhere('merchant_name', 'like', '%'.$search.'%');
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(15);

        return $this->successResponse(TransactionResource::collection($transactions));
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $service = app(TransactionService::class);
        $transaction = $service->createTransaction($request->validated(), $request->user());

        return $this->successResponse(new TransactionResource($transaction->load(['wallet.currency', 'category'])), 'Transaction created successfully', 201);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeTransaction($transaction, $request->user()->id);

        return $this->successResponse(new TransactionResource($transaction->load(['wallet.currency', 'category'])));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeTransaction($transaction, $request->user()->id);

        $service = app(TransactionService::class);
        $transaction = $service->updateTransaction($transaction, $request->validated());

        return $this->successResponse(new TransactionResource($transaction->load(['wallet.currency', 'category'])));
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeTransaction($transaction, $request->user()->id);

        $service = app(TransactionService::class);
        $service->deleteTransaction($transaction);

        return $this->successResponse(null, 'Transaction deleted successfully');
    }

    public function summary(Request $request): JsonResponse
    {
        $service = app(TransactionService::class);

        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date')) : null;

        $data = $service->getSummary($request->user(), $startDate, $endDate);

        return $this->successResponse($data);
    }

    private function authorizeTransaction(Transaction $transaction, int $userId): void
    {
        if ($transaction->user_id !== $userId) {
            abort(403);
        }
    }
}
