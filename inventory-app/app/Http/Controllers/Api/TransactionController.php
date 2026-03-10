<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactionService)
    {
    }

    /**
     * GET /api/transactions
     * Params: ?status=&from=&to=&customer_id=&per_page=20
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
            ->paginate($request->integer('per_page', 20));

        return TransactionResource::collection($transactions)->response();
    }

    /**
     * POST /api/transactions
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->createOrder($request->validated());

            return (new TransactionResource(
                $transaction->load(['customer', 'warehouse', 'items.product'])
            ))->response()->setStatusCode(201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/transactions/{transaction}
     */
    public function show(Transaction $transaction): JsonResponse
    {
        return (new TransactionResource(
            $transaction->load(['customer', 'warehouse', 'employee', 'items.product'])
        ))->response();
    }

    /**
     * PUT /api/transactions/{transaction}
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $transaction->update($validated);

        return (new TransactionResource($transaction->fresh()))->response();
    }

    /**
     * DELETE /api/transactions/{transaction}
     * Hanya boleh saat status pending.
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
     * PATCH /api/transactions/{transaction}/status
     */
    public function updateStatus(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,canceled',
        ]);

        try {
            $transaction = $this->transactionService->updateStatus($transaction, $request->status);

            return (new TransactionResource($transaction))->response();
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
