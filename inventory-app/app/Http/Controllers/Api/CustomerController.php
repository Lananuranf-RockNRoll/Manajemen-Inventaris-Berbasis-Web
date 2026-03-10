<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private const DEFAULT_CREDIT_LIMIT = 300.00; // $300 USD

    /**
     * GET /api/customers
     */
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->search, fn($q) => $q->where('name', 'LIKE', "%{$request->search}%")
                ->orWhere('email', 'LIKE', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
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

        // Default credit limit $300 USD jika tidak di-set
        if (!isset($validated['credit_limit'])) {
            $validated['credit_limit'] = self::DEFAULT_CREDIT_LIMIT;
        }

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
            'credit_limit' => 'sometimes|numeric|min:0',
            'status'       => 'sometimes|in:active,inactive,blacklisted',
        ]);

        $customer->update($validated);

        return (new CustomerResource($customer->fresh()))->response();
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

    /**
     * PATCH /api/customers/{id}/credit
     * Tambah atau kurangi credit limit customer (manager+)
     * Body: { "action": "add"|"subtract"|"set", "amount": 100.00 }
     */
    public function adjustCredit(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:add,subtract,set',
            'amount' => 'required|numeric|min:0',
        ]);

        $action = $validated['action'];
        $amount = (float) $validated['amount'];

        switch ($action) {
            case 'add':
                $customer->increment('credit_limit', $amount);
                $message = "Credit limit ditambah \${$amount}.";
                break;

            case 'subtract':
                $newLimit = max(0, (float) $customer->credit_limit - $amount);
                // Pastikan credit_limit tidak lebih kecil dari credit_used
                if ($newLimit < (float) $customer->credit_used) {
                    return response()->json([
                        'message' => "Tidak dapat mengurangi credit limit di bawah penggunaan saat ini (\${$customer->credit_used}).",
                    ], 422);
                }
                $customer->update(['credit_limit' => $newLimit]);
                $message = "Credit limit dikurangi \${$amount}.";
                break;

            case 'set':
                if ($amount < (float) $customer->credit_used) {
                    return response()->json([
                        'message' => "Credit limit tidak bisa lebih kecil dari penggunaan saat ini (\${$customer->credit_used}).",
                    ], 422);
                }
                $customer->update(['credit_limit' => $amount]);
                $message = "Credit limit diset ke \${$amount}.";
                break;
        }

        return (new CustomerResource($customer->fresh()))
            ->additional(['message' => $message])
            ->response();
    }

    /**
     * POST /api/customers/{id}/reset-credit
     * Reset credit_used ke 0 (admin only — untuk koreksi manual)
     */
    public function resetCreditUsed(Customer $customer): JsonResponse
    {
        $customer->update(['credit_used' => 0]);

        return (new CustomerResource($customer->fresh()))
            ->additional(['message' => 'Credit used direset ke $0.'])
            ->response();
    }
}
