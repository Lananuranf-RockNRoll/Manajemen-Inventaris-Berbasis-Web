<?php

namespace App\Http\Controllers\Api;

use App\Exports\InventoryExport;
use App\Exports\SalesExport;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // ─── Laporan Inventaris ───────────────────────────────────────────────

    public function inventoryExcel(Request $request)
    {
        $filename = 'laporan-inventaris-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new InventoryExport($request->warehouse_id), $filename);
    }

    public function inventoryPdf(Request $request)
    {
        $items = Inventory::with(['product.category', 'warehouse'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->orderBy('warehouse_id')
            ->get();

        $warehouseName = $request->warehouse_id
            ? Warehouse::find($request->warehouse_id)?->name
            : null;

        $pdf = Pdf::loadView('reports.inventory-pdf', compact('items', 'warehouseName'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-inventaris-' . now()->format('Ymd-His') . '.pdf');
    }

    // ─── Laporan Penjualan ────────────────────────────────────────────────

    public function salesExcel(Request $request)
    {
        $filename = 'laporan-penjualan-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(
            new SalesExport($request->from, $request->to, $request->status),
            $filename
        );
    }

    public function salesPdf(Request $request)
    {
        $transactions = Transaction::with(['customer', 'warehouse'])
            ->when($request->from, fn($q) => $q->whereDate('order_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('order_date', '<=', $request->to))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('order_date', 'desc')
            ->get();

        $from = $request->from;
        $to   = $request->to;

        $pdf = Pdf::loadView('reports.sales-pdf', compact('transactions', 'from', 'to'))
            ->setPaper('a4');

        return $pdf->download('laporan-penjualan-' . now()->format('Ymd-His') . '.pdf');
    }

    // ─── Dashboard PDF ────────────────────────────────────────────────────

    public function dashboardPdf()
    {
        $summary = [
            'total_revenue'   => Transaction::where('status', 'delivered')->sum('total_amount'),
            'total_orders'    => Transaction::count(),
            'total_products'  => Product::where('is_active', true)->count(),
            'total_customers' => Customer::count(),
            'pending_orders'  => Transaction::where('status', 'pending')->count(),
            'shipped_orders'  => Transaction::where('status', 'shipped')->count(),
            'canceled_orders' => Transaction::where('status', 'canceled')->count(),
        ];

        $topProducts = TransactionItem::with('product.category')
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(quantity * unit_price) as total_revenue')
            )
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'name'          => $item->product->name           ?? '-',
                'sku'           => $item->product->sku            ?? '-',
                'category_name' => $item->product->category->name ?? '-',
                'total_qty'     => $item->total_qty,
                'total_revenue' => $item->total_revenue,
            ]);

        $monthlySales = Transaction::where('status', '!=', 'canceled')
            ->whereYear('order_date', now()->year)
            ->select(
                DB::raw('MONTH(order_date) as month'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();

        $pdf = Pdf::loadView('reports.dashboard-pdf', compact(
            'summary', 'topProducts', 'monthlySales'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('dashboard-' . now()->format('Ymd-His') . '.pdf');
    }
}
