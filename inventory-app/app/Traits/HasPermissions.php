<?php

namespace App\Traits;

use App\Enums\Permission;
use App\Enums\Role;

/**
 * HasPermissions — tambahkan ke User model untuk cek hak akses berbasis Permission enum.
 *
 * Cara pakai:
 *   $user->can(Permission::PRODUCT_CREATE)        // true/false
 *   $user->canAny([Permission::PRODUCT_CREATE, Permission::PRODUCT_UPDATE])
 *   $user->mustCan(Permission::PRODUCT_DELETE)    // throw 403 jika tidak punya
 */
trait HasPermissions
{
    /**
     * Kembalikan array Permission milik user berdasarkan role-nya.
     * Di-cache di property agar tidak dihitung ulang dalam satu request.
     *
     * @return Permission[]
     */
    public function permissions(): array
    {
        if (! isset($this->resolvedPermissions)) {
            $role = Role::tryFrom($this->role ?? '');
            $this->resolvedPermissions = $role ? $role->permissions() : [];
        }

        return $this->resolvedPermissions;
    }

    /**
     * Cek apakah user punya permission tertentu.
     */
    public function can(Permission $permission, mixed $arguments = []): bool
    {
        return in_array($permission, $this->permissions(), strict: true);
    }

    /**
     * Cek apakah user punya setidaknya satu dari beberapa permission.
     *
     * @param  Permission[]  $permissions
     */
    public function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah user punya semua permission yang diberikan.
     *
     * @param  Permission[]  $permissions
     */
    public function canAll(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->can($permission)) {
                return false;
            }
        }

        return true;
    }

    // ── Role helpers (backward-compatible) ───────────────────────────────────

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, strict: true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN->value;
    }

    public function isManager(): bool
    {
        return $this->role === Role::MANAGER->value;
    }

    public function isStaff(): bool
    {
        return $this->role === Role::STAFF->value;
    }

    public function isViewer(): bool
    {
        return $this->role === Role::VIEWER->value;
    }
}
