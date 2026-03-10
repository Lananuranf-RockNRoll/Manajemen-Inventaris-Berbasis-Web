<?php

namespace App\Http\Controllers\Api;

use App\Exports\InventoryExport;
use App\Exports\SalesExport;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * GET /api/reports/inventory/excel
     */
    public function inventoryExcel(Request $request)
    {
        $filename = 'laporan-inventaris-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new InventoryExport($request->warehouse_id), $filename);
    }

    /**
     * GET /api/reports/sales/excel
     */
    public function salesExcel(Request $request)
    {
        $filename = 'laporan-penjualan-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new SalesExport($request->from, $request->to, $request->status),
            $filename
        );
    }

    /**
     * GET /api/reports/sales/pdf
     */
    public function salesPdf(Request $request)
    {
        $transactions = Transaction::with(['customer', 'warehouse'])
            ->when($request->from,   fn ($q) => $q->whereDate('order_date', '>=', $request->from))
            ->when($request->to,     fn ($q) => $q->whereDate('order_date', '<=', $request->to))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('order_date')
            ->get();

        $from = $request->from;
        $to   = $request->to;

        return Pdf::loadView('reports.sales-pdf', compact('transactions', 'from', 'to'))
            ->setPaper('a4')
            ->download('laporan-penjualan-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * GET /api/reports/dashboard/pdf
     */
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
            ->map(fn ($item) => [
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

        return Pdf::loadView('reports.dashboard-pdf', compact('summary', 'topProducts', 'monthlySales'))
            ->setPaper('a4')
            ->download('dashboard-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * GET /api/reports/transactions/{transaction}/invoice
     */
    public function invoicePdf(Transaction $transaction)
    {
        $transaction->load(['customer', 'warehouse', 'items.product']);

        $filename = 'invoice-' . str_replace(['/', ' '], '-', $transaction->order_number)
            . '-' . now()->format('Ymd') . '.pdf';

        return Pdf::loadView('reports.invoice-pdf', compact('transaction'))
            ->setPaper('a4')
            ->download($filename);
    }
}
