<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/summary
     */
    public function summary(): JsonResponse
    {
        $orderCounts = Transaction::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => [
                'total_revenue'      => $this->revenueQuery()->sum('total_amount'),
                'total_orders'       => Transaction::count(),
                'shipped_orders'     => $orderCounts['shipped']   ?? 0,
                'pending_orders'     => $orderCounts['pending']   ?? 0,
                'canceled_orders'    => $orderCounts['canceled']  ?? 0,
                'total_products'     => Product::count(),
                'total_warehouses'   => Warehouse::count(),
                'total_customers'    => Customer::count(),
                'low_stock_alerts'   => Inventory::lowStock()->count(),
                'top_category'       => $this->topCategoryName(),
                'revenue_this_month' => round((float) $this->revenueQuery()
                    ->whereYear('order_date', now()->year)
                    ->whereMonth('order_date', now()->month)
                    ->sum('total_amount'), 2),
            ],
        ]);
    }

    /**
     * GET /api/dashboard/top-products
     */
    public function topProducts(): JsonResponse
    {
        $topProducts = $this->transactionItemsQuery()
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'categories.name as category_name',
                DB::raw('SUM(transaction_items.quantity) as total_qty'),
                DB::raw('SUM(transaction_items.quantity * transaction_items.unit_price) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'categories.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return response()->json(['data' => $topProducts]);
    }

    /**
     * GET /api/dashboard/low-stock
     */
    public function lowStock(): JsonResponse
    {
        $items = Inventory::with(['product.category', 'warehouse'])
            ->lowStock()
            ->orderBy('qty_on_hand')
            ->limit(20)
            ->get()
            ->map(fn ($inv) => [
                'inventory_id'   => $inv->id,
                'product_id'     => $inv->product_id,
                'product_name'   => $inv->product->name,
                'product_sku'    => $inv->product->sku,
                'category_name'  => $inv->product->category->name,
                'warehouse_name' => $inv->warehouse->name,
                'qty_on_hand'    => $inv->qty_on_hand,
                'qty_available'  => $inv->qty_available,
                'min_stock'      => $inv->min_stock,
            ]);

        return response()->json(['data' => $items]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /** Query dasar transaksi non-canceled */
    private function revenueQuery()
    {
        return Transaction::where('status', '!=', 'canceled');
    }

    /** Query join transaction_items → products → categories → transactions (non-canceled) */
    private function transactionItemsQuery()
    {
        return DB::table('transaction_items')
            ->join('products',      'transaction_items.product_id',    '=', 'products.id')
            ->join('categories',    'products.category_id',            '=', 'categories.id')
            ->join('transactions',  'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', '!=', 'canceled');
    }

    /** Nama kategori dengan revenue tertinggi */
    private function topCategoryName(): string
    {
        $result = $this->transactionItemsQuery()
            ->select('categories.name', DB::raw('SUM(transaction_items.quantity * transaction_items.unit_price) as revenue'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->first();

        return $result?->name ?? '-';
    }
}
