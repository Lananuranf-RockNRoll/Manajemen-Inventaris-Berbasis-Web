<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckPermission — middleware granular berbasis Permission enum.
 *
 * Penggunaan di routes:
 *   ->middleware('permission:product.create')
 *   ->middleware('permission:product.create,product.update')  // salah satu sudah cukup
 *
 * Middleware ini beroperasi pada level Permission (bukan hanya Role),
 * sehingga tidak bisa di-bypass dengan manipulasi URL atau request langsung.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Cek setiap permission yang diminta — user harus punya setidaknya satu
        foreach ($permissions as $permissionValue) {
            $permission = Permission::tryFrom($permissionValue);

            if ($permission && $user->can($permission)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
            'required_permissions' => $permissions,
        ], 403);
    }
}
