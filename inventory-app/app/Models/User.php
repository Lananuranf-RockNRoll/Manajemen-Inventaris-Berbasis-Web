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

    // ── Relationships ───────────────────────────────────────────────────────────
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Override Laravel's Gate-based can() agar menggunakan Permission enum kita.
     * Jika argument bukan Permission enum, delegate ke parent (Gate).
     */
    public function can($permission, $arguments = []): bool
    {
        if ($permission instanceof Permission) {
            return in_array($permission, $this->permissions(), strict: true);
        }

        // Fallback ke Laravel Gate untuk kompatibilitas
        return parent::can($permission, $arguments);
    }
}
