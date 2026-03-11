<?php

namespace App\Traits;

use App\Enums\Permission;
use App\Enums\Role;

/**
 * HasPermissions — RBAC berbasis Permission enum.
 *
 * PENTING: Tidak boleh mendefinisikan method yang sudah ada di
 * Illuminate\Foundation\Auth\User yaitu: can(), canAny(), canAll().
 * Gunakan hasPermission(), hasAnyPermission(), hasAllPermissions() sebagai gantinya.
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
     * Gunakan ini di dalam controller/middleware.
     *
     * Contoh: $user->hasPermission(Permission::PRODUCT_CREATE)
     */
    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), strict: true);
    }

    /**
     * Cek apakah user punya setidaknya satu dari beberapa permission.
     *
     * @param  Permission[]  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
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
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    // ── Role helpers ─────────────────────────────────────────────────────────

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
