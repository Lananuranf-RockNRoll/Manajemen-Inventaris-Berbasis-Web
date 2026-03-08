<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function salesReport(string $period, int $year, ?int $month): array
    {
        $normalizedPeriod = $this->normalizePeriod($period);
        $selectedMonth = $month ?? now()->month;

        return [
            'period' => $normalizedPeriod,
            'year' => $year,
            'month' => $month,
            'total_revenue' => $this->baseSalesQuery($year)->sum('total_amount'),
            'breakdown' => $this->buildSalesBreakdown($normalizedPeriod, $year, $selectedMonth),
        ];
    }

    public function inventoryReport(?int $warehouseId): array
    {
        $inventories = Inventory::with(['product.category', 'warehouse'])
            ->when($warehouseId, fn (Builder $query) => $query->where('warehouse_id', $warehouseId))
            ->get();

        $warehouses = $inventories
            ->groupBy(fn (Inventory $inventory) => $inventory->warehouse->name)
            ->map(fn (Collection $group, string $warehouseName) => $this->mapWarehouseInventoryReport($group, $warehouseName))
            ->values()
            ->all();

        return ['warehouses' => $warehouses];
    }

    public function export(string $format, string $type, string $period, int $year)
    {
        abort(501, 'Export belum diimplementasikan. Pasang maatwebsite/excel dan barryvdh/laravel-dompdf lalu buat view.');
    }

    private function normalizePeriod(string $period): string
    {
        $allowedPeriods = ['daily', 'weekly', 'monthly', 'yearly'];

        return in_array($period, $allowedPeriods, true) ? $period : 'monthly';
    }

    private function buildSalesBreakdown(string $period, int $year, int $month): array
    {
        return match ($period) {
            'daily' => $this->dailySales($year, $month),
            'weekly' => $this->weeklySales($year),
            'monthly' => $this->monthlySales($year),
            'yearly' => $this->yearlySales(),
            default => $this->monthlySales($year),
        };
    }

    private function baseSalesQuery(int $year): Builder
    {
        return Transaction::query()
            ->where('status', '!=', 'canceled')
            ->whereYear('order_date', $year);
    }

    private function mapWarehouseInventoryReport(Collection $inventories, string $warehouseName): array
    {
        return [
            'warehouse_name' => $warehouseName,
            'total_products' => $inventories->count(),
            'total_qty_on_hand' => $inventories->sum('qty_on_hand'),
            'low_stock_items' => $inventories->filter(fn (Inventory $inventory) => $inventory->is_low_stock)->count(),
            'items' => $inventories
                ->map(fn (Inventory $inventory) => $this->mapInventoryItem($inventory))
                ->values()
                ->all(),
        ];
    }

    private function mapInventoryItem(Inventory $inventory): array
    {
        return [
            'product_name' => $inventory->product->name,
            'product_sku' => $inventory->product->sku,
            'category_name' => $inventory->product->category->name,
            'qty_on_hand' => $inventory->qty_on_hand,
            'qty_reserved' => $inventory->qty_reserved,
            'qty_available' => $inventory->qty_available,
            'min_stock' => $inventory->min_stock,
            'is_low_stock' => $inventory->is_low_stock,
        ];
    }

    private function dailySales(int $year, int $month): array
    {
        return $this->baseSalesQuery($year)
            ->whereMonth('order_date', $month)
            ->select(
                DB::raw('DATE(order_date) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function weeklySales(int $year): array
    {
        return $this->baseSalesQuery($year)
            ->select(
                DB::raw('WEEK(order_date) as week'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('week')
            ->orderBy('week')
            ->get()
            ->toArray();
    }

    private function monthlySales(int $year): array
    {
        return $this->baseSalesQuery($year)
            ->select(
                DB::raw('MONTH(order_date) as month'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function yearlySales(): array
    {
        return Transaction::query()
            ->where('status', '!=', 'canceled')
            ->select(
                DB::raw('YEAR(order_date) as year'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->toArray();
    }
}
