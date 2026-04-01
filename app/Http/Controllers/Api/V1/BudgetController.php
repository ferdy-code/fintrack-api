<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $budgets = $request->user()->budgets()
            ->with('category')
            ->where('is_active', true)
            ->get();

        return $this->successResponse(BudgetResource::collection($budgets));
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $exists = Budget::where('user_id', $request->user()->id)
            ->where('category_id', $request->input('category_id'))
            ->where('period', $request->input('period'))
            ->exists();

        if ($exists) {
            return $this->errorResponse('Budget already exists for this category and period', 422);
        }

        $budget = Budget::create(array_merge($request->validated(), [
            'user_id' => $request->user()->id,
            'currency_code' => $request->user()->default_currency_code,
        ]));

        $budget->load('category');

        return $this->successResponse(new BudgetResource($budget), 'Budget created successfully', 201);
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($budget, $request->user()->id);

        return $this->successResponse(new BudgetResource($budget->load('category')));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($budget, $request->user()->id);

        $budget->update($request->validated());

        return $this->successResponse(new BudgetResource($budget->load('category')), 'Budget updated successfully');
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($budget, $request->user()->id);

        $budget->delete();

        return $this->successResponse(null, 'Budget deleted successfully');
    }

    public function overview(Request $request): JsonResponse
    {
        $budgets = $request->user()->budgets()
            ->with('category')
            ->where('is_active', true)
            ->get();

        return $this->successResponse(BudgetResource::collection($budgets));
    }

    private function authorizeBudget(Budget $budget, int $userId): void
    {
        if ($budget->user_id !== $userId) {
            abort(403);
        }
    }
}
