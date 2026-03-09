<?php

namespace App\Http\Controllers\Api;

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
     * Params: ?warehouse_id=1&product_id=2&low_stock=true&per_page=20
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
     */
    public function show(Inventory $inventory): JsonResponse
    {
        return (new InventoryResource(
            $inventory->load(['product.category', 'warehouse'])
        ))->response();
    }

    /**
     * PUT /api/inventory/{inventory}
     * Manual restock / update.
     */
    public function update(Request $request, Inventory $inventory): JsonResponse
    {
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
