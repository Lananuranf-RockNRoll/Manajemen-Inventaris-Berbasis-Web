<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'data'    => [
                'user'        => $this->formatUser($user),
                'token'       => $token,
                'permissions' => $this->formatPermissions($user),
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * GET /api/auth/me
     * Mengembalikan profil user beserta daftar permissions aktif.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                ...$this->formatUser($user),
                'permissions' => $this->formatPermissions($user),
            ],
        ]);
    }

    /**
     * GET /api/auth/permissions
     * Endpoint khusus untuk frontend mengambil daftar permission user yang sedang login.
     * Berguna untuk menyembunyikan/menampilkan UI berdasarkan hak akses.
     */
    public function permissions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'role'        => $user->role,
                'permissions' => $this->formatPermissions($user),
            ],
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function formatUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'is_active'  => $user->is_active,
            'created_at' => $user->created_at,
        ];
    }

    /**
     * Kembalikan array string dari permission values — mudah dikonsumsi frontend.
     * Contoh: ['product.view', 'product.create', 'transaction.view', ...]
     */
    private function formatPermissions(User $user): array
    {
        return array_map(
            fn ($permission) => $permission->value,
            $user->permissions()
        );
    }
}
