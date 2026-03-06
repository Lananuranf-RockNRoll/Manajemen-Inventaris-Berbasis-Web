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
        $totalRevenue = Transaction::where('status', '!=', 'canceled')
            ->sum('total_amount');

        $orderCounts = Transaction::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $revenueThisMonth = Transaction::where('status', '!=', 'canceled')
            ->whereYear('order_date', now()->year)
            ->whereMonth('order_date', now()->month)
            ->sum('total_amount');

        $lowStockCount = Inventory::lowStock()->count();

        $topCategory = DB::table('transaction_items')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', '!=', 'canceled')
            ->select('categories.name', DB::raw('SUM(transaction_items.quantity * transaction_items.unit_price) as revenue'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->first();

        return response()->json([
            'data' => [
                'total_revenue'        => round((float) $totalRevenue, 2),
                'total_orders'         => Transaction::count(),
                'shipped_orders'       => $orderCounts['shipped'] ?? 0,
                'pending_orders'       => $orderCounts['pending'] ?? 0,
                'canceled_orders'      => $orderCounts['canceled'] ?? 0,
                'total_products'       => Product::count(),
                'total_warehouses'     => Warehouse::count(),
                'total_customers'      => Customer::count(),
                'low_stock_alerts'     => $lowStockCount,
                'top_category'         => $topCategory?->name ?? '-',
                'revenue_this_month'   => round((float) $revenueThisMonth, 2),
            ],
        ]);
    }

    /**
     * GET /api/dashboard/top-products
     * Returns top 10 products by revenue
     */
    public function topProducts(): JsonResponse
    {
        $topProducts = DB::table('transaction_items')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', '!=', 'canceled')
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
     * Returns products with low stock across all warehouses
     */
    public function lowStock(): JsonResponse
    {
        $lowStock = Inventory::with(['product.category', 'warehouse'])
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

        return response()->json(['data' => $lowStock]);
    }
}
