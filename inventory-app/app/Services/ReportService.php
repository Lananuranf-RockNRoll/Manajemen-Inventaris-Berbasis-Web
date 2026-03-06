<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Laporan penjualan berdasarkan periode.
     */
    public function salesReport(string $period, int $year, ?int $month): array
    {
        $query = Transaction::where('status', '!=', 'canceled')
            ->whereYear('order_date', $year);

        $data = match ($period) {
            'daily'   => $this->dailySales($query, $year, $month ?? now()->month),
            'weekly'  => $this->weeklySales($query, $year),
            'monthly' => $this->monthlySales($query, $year),
            'yearly'  => $this->yearlySales(),
            default   => $this->monthlySales($query, $year),
        };

        return [
            'period'        => $period,
            'year'          => $year,
            'month'         => $month,
            'total_revenue' => round(Transaction::where('status', '!=', 'canceled')
                ->whereYear('order_date', $year)->sum('total_amount'), 2),
            'breakdown'     => $data,
        ];
    }

    /**
     * Laporan inventaris per gudang.
     */
    public function inventoryReport(?int $warehouseId): array
    {
        $query = Inventory::with(['product.category', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId));

        $inventories = $query->get();

        $summary = $inventories->groupBy(fn ($inv) => $inv->warehouse->name)
            ->map(fn ($group, $warehouseName) => [
                'warehouse_name'    => $warehouseName,
                'total_products'    => $group->count(),
                'total_qty_on_hand' => $group->sum('qty_on_hand'),
                'low_stock_items'   => $group->filter(fn ($inv) => $inv->is_low_stock)->count(),
                'items'             => $group->map(fn ($inv) => [
                    'product_name'   => $inv->product->name,
                    'product_sku'    => $inv->product->sku,
                    'category_name'  => $inv->product->category->name,
                    'qty_on_hand'    => $inv->qty_on_hand,
                    'qty_reserved'   => $inv->qty_reserved,
                    'qty_available'  => $inv->qty_available,
                    'min_stock'      => $inv->min_stock,
                    'is_low_stock'   => $inv->is_low_stock,
                ])->values(),
            ])->values();

        return ['warehouses' => $summary];
    }

    /**
     * Export laporan ke format Excel atau PDF.
     * TODO: Implement actual Excel export using maatwebsite/excel
     * TODO: Implement actual PDF export using barryvdh/laravel-dompdf
     */
    public function export(string $format, string $type, string $period, int $year)
    {
        // TODO: Implement Excel export using Maatwebsite\Excel\Facades\Excel::download()
        // TODO: Implement PDF export using Barryvdh\DomPDF\Facade\Pdf::loadView()
        // Placeholder — actual implementation requires view files
        abort(501, 'Export belum diimplementasikan. Pasang maatwebsite/excel dan barryvdh/laravel-dompdf lalu buat view.');
    }

    // ── Private helpers ──────────────────────────────────────────────────────────
    private function dailySales($query, int $year, int $month): array
    {
        return $query->whereMonth('order_date', $month)
            ->select(DB::raw('DATE(order_date) as date'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function weeklySales($query, int $year): array
    {
        return $query->select(DB::raw('WEEK(order_date) as week'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('week')
            ->orderBy('week')
            ->get()
            ->toArray();
    }

    private function monthlySales($query, int $year): array
    {
        return $query->select(DB::raw('MONTH(order_date) as month'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function yearlySales(): array
    {
        return Transaction::where('status', '!=', 'canceled')
            ->select(DB::raw('YEAR(order_date) as year'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->toArray();
    }
}
