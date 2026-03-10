<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole — middleware berbasis nama role (dipertahankan untuk kompatibilitas).
 * Untuk akses granular, gunakan CheckPermission.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! in_array($user->role, $roles, strict: true)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
            ], 403);
        }

        return $next($request);
    }
}
