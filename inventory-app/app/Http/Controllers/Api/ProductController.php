<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * Akses: semua role (viewer, staff, manager, admin)
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::with('category')
            ->when($request->search,      fn ($q) => $q->search($request->search))
            ->when($request->category_id, fn ($q) => $q->byCategory((int) $request->category_id))
            ->when($request->active,      fn ($q) => $q->active())
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return ProductResource::collection($products)->response();
    }

    /**
     * POST /api/products
     * Akses: manager, admin
     * Double-checked: middleware permission:product.create + FormRequest::authorize()
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/products/{product}
     * Akses: semua role
     */
    public function show(Product $product): JsonResponse
    {
        return (new ProductResource(
            $product->load(['category', 'inventories.warehouse'])
        ))->response();
    }

    /**
     * PUT /api/products/{product}
     * Akses: manager, admin
     * Double-checked: middleware permission:product.update + FormRequest::authorize()
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return (new ProductResource($product->load('category')))->response();
    }

    /**
     * DELETE /api/products/{product}
     * Akses: admin only
     * Double-checked: middleware permission:product.delete + cek di bawah
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        // Lapisan 2 — defense in depth
        if (! $request->user()->can(Permission::PRODUCT_DELETE)) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk menghapus produk.',
            ], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
