<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransferInventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Services\InventoryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    /**
     * GET /api/inventory
     * Akses: semua role
     */
    public function index(Request $request): JsonResponse
    {
        $inventory = Inventory::with(['product.category', 'warehouse'])
            ->when($request->warehouse_id, fn ($q) => $q->byWarehouse((int) $request->warehouse_id))
            ->when($request->product_id,   fn ($q) => $q->where('product_id', (int) $request->product_id))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->paginate($request->integer('per_page', 20));

        return InventoryResource::collection($inventory)->response();
    }

    /**
     * GET /api/inventory/{inventory}
     * Akses: semua role
     */
    public function show(Inventory $inventory): JsonResponse
    {
        return (new InventoryResource(
            $inventory->load(['product.category', 'warehouse'])
        ))->response();
    }

    /**
     * PUT /api/inventory/{inventory}
     * Manual restock / update. Akses: manager, admin
     */
    public function update(Request $request, Inventory $inventory): JsonResponse
    {
        // Lapisan 2 — defense in depth
        if (! $request->user()->can(Permission::INVENTORY_UPDATE)) {
            return response()->json(['message' => 'Anda tidak memiliki izin untuk mengubah inventaris.'], 403);
        }

        $validated = $request->validate([
            'qty_on_hand'  => 'sometimes|integer|min:0',
            'qty_reserved' => 'sometimes|integer|min:0',
            'min_stock'    => 'sometimes|integer|min:0',
            'max_stock'    => 'sometimes|integer|min:1',
        ]);

        $inventory->update($validated);

        if (isset($validated['qty_on_hand'])) {
            $inventory->update(['last_restocked_at' => now()]);
        }

        return (new InventoryResource($inventory->fresh(['product', 'warehouse'])))->response();
    }

    /**
     * POST /api/inventory/transfer
     * Akses: manager, admin
     * Double-checked: middleware permission:inventory.transfer + FormRequest::authorize()
     */
    public function transfer(TransferInventoryRequest $request): JsonResponse
    {
        try {
            $this->inventoryService->transferStock(
                productId:       $request->integer('product_id'),
                fromWarehouseId: $request->integer('from_warehouse_id'),
                toWarehouseId:   $request->integer('to_warehouse_id'),
                qty:             $request->integer('quantity'),
            );

            return response()->json(['message' => 'Transfer stok berhasil.']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/inventory/alerts/low-stock
     * Akses: semua role
     */
    public function lowStock(Request $request): JsonResponse
    {
        $inventory = Inventory::with(['product.category', 'warehouse'])
            ->lowStock()
            ->when($request->warehouse_id, fn ($q) => $q->byWarehouse((int) $request->warehouse_id))
            ->paginate($request->integer('per_page', 20));

        return InventoryResource::collection($inventory)->response();
    }
}
