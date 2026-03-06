<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * GET /api/customers
     */
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->search, fn ($q) => $q->where('name', 'LIKE', "%{$request->search}%")
                ->orWhere('email', 'LIKE', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return CustomerResource::collection($customers)->response();
    }

    /**
     * POST /api/customers
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => 'nullable|email|max:100|unique:customers,email',
            'phone'        => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'status'       => 'sometimes|in:active,inactive,blacklisted',
        ]);

        $customer = Customer::create($validated);

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    /**
     * GET /api/customers/{id}
     */
    public function show(Customer $customer): JsonResponse
    {
        return (new CustomerResource($customer->loadCount('transactions')))->response();
    }

    /**
     * PUT /api/customers/{id}
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:100',
            'email'        => 'nullable|email|max:100|unique:customers,email,' . $customer->id,
            'phone'        => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'status'       => 'sometimes|in:active,inactive,blacklisted',
        ]);

        $customer->update($validated);

        return (new CustomerResource($customer))->response();
    }

    /**
     * DELETE /api/customers/{id}
     */
    public function destroy(Customer $customer): JsonResponse
    {
        if ($customer->transactions()->exists()) {
            return response()->json([
                'message' => 'Customer tidak dapat dihapus karena memiliki transaksi.',
            ], 422);
        }

        $customer->delete();

        return response()->json(['message' => 'Customer berhasil dihapus.']);
    }
}
