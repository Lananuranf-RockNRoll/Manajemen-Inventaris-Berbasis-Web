<?php

namespace App\Http\Controllers\Api;

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
     * Params: ?search=xeon&category_id=1&per_page=15&active=1
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::with('category')
            ->when($request->search,      fn ($q) => $q->search($request->search))
            ->when($request->category_id, fn ($q) => $q->byCategory((int) $request->category_id))
            ->when($request->active,      fn ($q) => $q->active())
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return ProductResource::collection($products)->response();
    }

    /**
     * POST /api/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product->load('category')))->response()->setStatusCode(201);
    }

    /**
     * GET /api/products/{id}
     */
    public function show(Product $product): JsonResponse
    {
        return (new ProductResource(
            $product->load(['category', 'inventories.warehouse'])
        ))->response();
    }

    /**
     * PUT /api/products/{id}
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return (new ProductResource($product->load('category')))->response();
    }

    /**
     * DELETE /api/products/{id}  — soft delete
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
