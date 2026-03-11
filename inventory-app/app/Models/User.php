<?php

namespace App\Models;

use App\Enums\Permission;
use App\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasPermissions, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    // ── Permission check ──────────────────────────────────────────────────────

    /**
     * Override Laravel Gate can() agar support Permission enum kita.
     *
     * - Jika argument adalah Permission enum  → cek via resolvedPermissions (RBAC kita)
     * - Jika argument adalah string/lainnya   → delegate ke Laravel Gate (Sanctum dll)
     *
     * Ini penting agar createToken(), Sanctum middleware, dan Gate::allows()
     * tetap jalan normal tanpa konflik.
     *
     * @param  Permission|string  $permission
     * @param  mixed              $arguments
     */
    public function can($permission, $arguments = []): bool
    {
        if ($permission instanceof Permission) {
            return in_array($permission, $this->permissions(), strict: true);
        }

        // Fallback ke Laravel Gate untuk semua pengecekan internal framework
        return parent::can($permission, $arguments);
    }
}
