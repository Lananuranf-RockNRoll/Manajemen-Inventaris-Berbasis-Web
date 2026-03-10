<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactionService)
    {
    }

    /**
     * GET /api/transactions
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
     * Akses: staff, manager, admin
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
     * Edit field notes saja. Akses: staff, manager, admin.
     *
     * Batasan tambahan per role:
     * - Staff: hanya bisa edit notes transaksi berstatus pending yang dia buat.
     * - Manager & Admin: bisa edit notes semua transaksi.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $user = $request->user();

        // Staff hanya bisa edit transaksi pending miliknya sendiri
        if ($user->isStaff()) {
            if ($transaction->status !== 'pending') {
                return response()->json([
                    'message' => 'Staff hanya dapat mengedit catatan transaksi berstatus pending.',
                ], 403);
            }

            if ($transaction->employee_id && $transaction->employee?->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Anda hanya dapat mengedit transaksi yang Anda buat.',
                ], 403);
            }
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $transaction->update($validated);

        return (new TransactionResource($transaction->fresh()))->response();
    }

    /**
     * DELETE /api/transactions/{transaction}
     * Akses: admin only.
     * Aturan bisnis: hanya transaksi berstatus pending yang dapat dihapus.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        // Lapisan 2: validasi di controller (defense in depth)
        // Lapisan 1 sudah di middleware permission:transaction.delete (admin only)
        if ($transaction->status !== 'pending') {
            return response()->json([
                'message' => 'Hanya transaksi berstatus pending yang dapat dihapus.',
            ], 422);
        }

        DB::transaction(function () use ($transaction): void {
            $transaction->items()->delete();
            $transaction->delete();
        });

        return response()->json(['message' => 'Transaksi berhasil dihapus.']);
    }

    /**
     * PATCH /api/transactions/{transaction}/status
     * Akses: manager, admin.
     *
     * Batasan tambahan:
     * - Manager: tidak bisa mengubah status transaksi yang sudah 'delivered'.
     * - Admin: bebas mengubah status sesuai flow.
     */
    public function updateStatus(Request $request, Transaction $transaction): JsonResponse
    {
        $user = $request->user();

        // Manager tidak bisa menyentuh transaksi yang sudah delivered
        if ($user->isManager() && $transaction->status === 'delivered') {
            return response()->json([
                'message' => 'Manajer tidak dapat mengubah status transaksi yang sudah selesai (delivered).',
            ], 403);
        }

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
