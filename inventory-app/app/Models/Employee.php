<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'warehouse_id',
        'name',
        'email',
        'phone',
        'job_title',
        'department',
        'hire_date',
        'salary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'salary'    => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
