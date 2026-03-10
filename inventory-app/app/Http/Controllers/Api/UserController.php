<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Admin only: list all users
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->search, fn($q) => $q->where('name', 'LIKE', "%{$request->search}%")
                ->orWhere('email', 'LIKE', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'from'         => $users->firstItem(),
                'to'           => $users->lastItem(),
            ],
        ]);
    }

    /**
     * POST /api/users
     * Admin only: create manager, staff, or viewer
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:manager,staff,viewer',
            'is_active'=> 'sometimes|boolean',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role'      => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'data'    => $this->userArray($user),
        ], 201);
    }

    /**
     * GET /api/users/{id}
     */
    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $this->userArray($user)]);
    }

    /**
     * PUT /api/users/{id}
     * Admin only: update user (cannot change own role or deactivate self)
     */
    public function update(Request $request, User $user): JsonResponse
    {
        // Admin tidak bisa mengubah dirinya sendiri via endpoint ini (gunakan /auth/me)
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Gunakan endpoint profile untuk mengubah akun sendiri.'], 422);
        }

        $validated = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'email'     => 'sometimes|email|max:100|unique:users,email,' . $user->id,
            'password'  => 'sometimes|string|min:8',
            'role'      => 'sometimes|in:manager,staff,viewer',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User berhasil diperbarui.',
            'data'    => $this->userArray($user->fresh()),
        ]);
    }

    /**
     * DELETE /api/users/{id}
     * Admin only: soft delete user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    /**
     * PATCH /api/users/{id}/toggle-active
     * Admin only: toggle active status
     */
    public function toggleActive(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Tidak dapat menonaktifkan akun sendiri.'], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'User diaktifkan.' : 'User dinonaktifkan.',
            'data'    => $this->userArray($user->fresh()),
        ]);
    }

    private function userArray(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'is_active'  => $user->is_active,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
