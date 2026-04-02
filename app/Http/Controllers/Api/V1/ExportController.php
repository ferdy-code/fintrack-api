<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(private ExportService $exportService) {}

    public function transactionsCsv(Request $request)
    {
        $filters = $this->getFilters($request);

        return $this->exportService->exportTransactionsCsv($request->user(), $filters);
    }

    public function transactionsPdf(Request $request)
    {
        $filters = $this->getFilters($request);

        return $this->exportService->exportTransactionsPdf($request->user(), $filters);
    }

    public function monthlyReportPdf(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2030'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        return $this->exportService->exportMonthlyReportPdf(
            $request->user(),
            $validated['year'],
            $validated['month']
        );
    }

    private function getFilters(Request $request): array
    {
        return $request->validate([
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'wallet_id' => ['sometimes', 'nullable', 'integer'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
            'type' => ['sometimes', 'nullable', 'string', 'in:income,expense,transfer'],
        ]);
    }
}
