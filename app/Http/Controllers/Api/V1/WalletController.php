<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreWalletRequest;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wallets = Wallet::where('user_id', $request->user()->id)
            ->with('currency')
            ->orderBy('name')
            ->get();

        return $this->successResponse(WalletResource::collection($wallets));
    }

    public function store(StoreWalletRequest $request): JsonResponse
    {
        $wallet = Wallet::create(array_merge($request->validated(), [
            'user_id' => $request->user()->id,
        ]));

        $wallet->load('currency');

        return $this->successResponse(new WalletResource($wallet), 'Wallet created successfully', 201);
    }

    public function show(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorizeWallet($wallet, $request->user()->id);

        return $this->successResponse(new WalletResource($wallet->load('currency')));
    }

    public function update(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorizeWallet($wallet, $request->user()->id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['sometimes', 'string', 'max:7'],
            'balance' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $wallet->update($validated);
        $wallet->load('currency');

        return $this->successResponse(new WalletResource($wallet), 'Wallet updated successfully');
    }

    public function destroy(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorizeWallet($wallet, $request->user()->id);

        if ($wallet->transactions()->exists()) {
            return $this->errorResponse('Cannot delete wallet with existing transactions', 422);
        }

        $wallet->delete();

        return $this->successResponse(null, 'Wallet deleted successfully');
    }

    private function authorizeWallet(Wallet $wallet, int $userId): void
    {
        if ($wallet->user_id !== $userId) {
            abort(403);
        }
    }
}
