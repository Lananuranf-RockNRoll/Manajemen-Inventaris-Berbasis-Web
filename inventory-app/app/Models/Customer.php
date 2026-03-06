<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'credit_limit',
        'credit_used',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'credit_used'  => 'decimal:2',
        ];
    }

    // ── Computed Attributes ─────────────────────────────────────────────────────
    public function getCreditAvailableAttribute(): float
    {
        return round((float) $this->credit_limit - (float) $this->credit_used, 2);
    }

    // ── Relationships ───────────────────────────────────────────────────────────
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
