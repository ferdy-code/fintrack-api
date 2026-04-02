<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(): JsonResponse
    {
        $currencies = Currency::orderBy('code')->get();

        return $this->successResponse(CurrencyResource::collection($currencies));
    }

    public function rates(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['sometimes', 'string', 'size:3', 'exists:currencies,code'],
            'to' => ['sometimes', 'string', 'size:3', 'exists:currencies,code'],
        ]);

        $currencies = Currency::orderBy('code')->get();

        return $this->successResponse(CurrencyResource::collection($currencies));
    }
}
