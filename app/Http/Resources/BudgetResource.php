<?php

namespace App\Http\Resources;

use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $budgetService = app(BudgetService::class);
        $stats = $budgetService->getBudgetWithStats($this->resource);

        return [
            'id' => $this->id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'amount' => (float) $this->amount,
            'currency' => [
                'code' => $this->currency_code,
            ],
            'period' => $this->period,
            'alert_threshold' => (float) $this->alert_threshold,
            'is_active' => $this->is_active,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'spent' => $stats['spent'],
            'remaining' => $stats['remaining'],
            'percentage_used' => $stats['percentage_used'],
            'is_over_budget' => $stats['is_over_budget'],
            'period_start' => $stats['period_start'],
            'period_end' => $stats['period_end'],
            'days_remaining' => $stats['days_remaining'],
            'created_at' => $this->created_at,
        ];
    }
}
