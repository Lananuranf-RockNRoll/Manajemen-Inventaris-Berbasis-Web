<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * GET /api/warehouses
     */
    public function index(Request $request): JsonResponse
    {
        $warehouses = Warehouse::query()
            ->when($request->search, fn ($q) => $q->where('name', 'LIKE', "%{$request->search}%"))
            ->when($request->region, fn ($q) => $q->where('region', $request->region))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return WarehouseResource::collection($warehouses)->response();
    }

    /**
     * POST /api/warehouses
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'region'      => 'nullable|string|max:50',
            'country'     => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'city'        => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'address'     => 'nullable|string',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:100',
            'is_active'   => 'boolean',
        ]);

        $warehouse = Warehouse::create($validated);

        return (new WarehouseResource($warehouse))->response()->setStatusCode(201);
    }

    /**
     * GET /api/warehouses/{id}
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        return (new WarehouseResource(
            $warehouse->load('employees')
        ))->response();
    }

    /**
     * PUT /api/warehouses/{id}
     */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'region'      => 'nullable|string|max:50',
            'country'     => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'city'        => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'address'     => 'nullable|string',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:100',
            'is_active'   => 'boolean',
        ]);

        $warehouse->update($validated);

        return (new WarehouseResource($warehouse))->response();
    }

    /**
     * DELETE /api/warehouses/{id}
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        if ($warehouse->inventories()->exists() || $warehouse->transactions()->exists()) {
            return response()->json([
                'message' => 'Gudang tidak dapat dihapus karena memiliki data inventaris atau transaksi.',
            ], 422);
        }

        $warehouse->delete();

        return response()->json(['message' => 'Gudang berhasil dihapus.']);
    }
}
