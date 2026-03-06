<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    /**
     * GET /api/reports/sales
     * Params: ?period=monthly&year=2024&month=1
     */
    public function sales(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'sometimes|in:daily,weekly,monthly,yearly',
            'year'   => 'sometimes|integer|min:2000|max:2100',
            'month'  => 'sometimes|integer|min:1|max:12',
        ]);

        $data = $this->reportService->salesReport(
            period: $validated['period'] ?? 'monthly',
            year:   $validated['year']   ?? now()->year,
            month:  $validated['month']  ?? null,
        );

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/reports/inventory
     * Params: ?warehouse_id=1
     */
    public function inventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $data = $this->reportService->inventoryReport(
            warehouseId: $validated['warehouse_id'] ?? null
        );

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/reports/export
     * Params: ?format=excel&type=sales&period=monthly&year=2024
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:excel,pdf',
            'type'   => 'required|in:sales,inventory',
            'period' => 'sometimes|in:daily,weekly,monthly,yearly',
            'year'   => 'sometimes|integer|min:2000|max:2100',
        ]);

        return $this->reportService->export(
            format:      $validated['format'],
            type:        $validated['type'],
            period:      $validated['period'] ?? 'monthly',
            year:        $validated['year']   ?? now()->year,
        );
    }
}
