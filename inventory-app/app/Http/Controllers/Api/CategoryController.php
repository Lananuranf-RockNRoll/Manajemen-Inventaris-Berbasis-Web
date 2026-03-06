<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * GET /api/categories
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->when($request->search, fn ($q) => $q->where('name', 'LIKE', "%{$request->search}%"))
            ->when($request->active, fn ($q) => $q->active())
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return CategoryResource::collection($categories)->response();
    }

    /**
     * POST /api/categories
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = Category::create($validated);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * GET /api/categories/{id}
     */
    public function show(Category $category): JsonResponse
    {
        return (new CategoryResource($category->loadCount('products')))->response();
    }

    /**
     * PUT /api/categories/{id}
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:100|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return (new CategoryResource($category))->response();
    }

    /**
     * DELETE /api/categories/{id}
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena memiliki produk.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}
