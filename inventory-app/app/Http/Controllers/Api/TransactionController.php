<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    /**
     * GET /api/transactions
     * Params: ?status=pending&from=2024-01-01&to=2024-12-31&customer_id=1&per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $transactions = Transaction::with(['customer', 'warehouse', 'employee'])
            ->when($request->status,      fn ($q) => $q->byStatus($request->status))
            ->when($request->customer_id, fn ($q) => $q->byCustomer((int) $request->customer_id))
            ->when(
                $request->from && $request->to,
                fn ($q) => $q->dateRange($request->from, $request->to)
            )
            ->latest('order_date')
            ->paginate($request->per_page ?? 20);

        return TransactionResource::collection($transactions)->response();
    }

    /**
     * POST /api/transactions
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $transaction = $this->transactionService->createOrder($request->validated());

        return (new TransactionResource(
            $transaction->load(['customer', 'warehouse', 'items.product'])
        ))->response()->setStatusCode(201);
    }

    /**
     * GET /api/transactions/{id}
     */
    public function show(Transaction $transaction): JsonResponse
    {
        return (new TransactionResource(
            $transaction->load(['customer', 'warehouse', 'employee', 'items.product'])
        ))->response();
    }

    /**
     * PUT /api/transactions/{id}
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $transaction->update($validated);

        return (new TransactionResource($transaction))->response();
    }

    /**
     * DELETE /api/transactions/{id}
     * Only allowed if status is pending
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        if ($transaction->status !== 'pending') {
            return response()->json([
                'message' => 'Hanya transaksi berstatus pending yang dapat dihapus.',
            ], 422);
        }

        $transaction->items()->delete();
        $transaction->delete();

        return response()->json(['message' => 'Transaksi berhasil dihapus.']);
    }

    /**
     * PATCH /api/transactions/{id}/status
     */
    public function updateStatus(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,canceled',
        ]);

        $transaction = $this->transactionService->updateStatus(
            $transaction,
            $request->status
        );

        return (new TransactionResource($transaction))->response();
    }
}
