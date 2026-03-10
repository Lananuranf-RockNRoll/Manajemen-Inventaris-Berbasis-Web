<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'qty_on_hand',
        'qty_reserved',
        'min_stock',
        'max_stock',
        'last_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_restocked_at' => 'datetime',
        ];
    }

    // ── Computed Attributes ─────────────────────────────────────────────────────

    /** Stok tersedia = on_hand - reserved (tidak pernah negatif) */
    public function getQtyAvailableAttribute(): int
    {
        return max(0, $this->qty_on_hand - $this->qty_reserved);
    }

    /** True jika stok tersedia <= min_stock threshold */
    public function getIsLowStockAttribute(): bool
    {
        return $this->qty_available <= $this->min_stock;
    }

    // ── Relationships ───────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw('(qty_on_hand - qty_reserved) <= min_stock');
    }

    public function scopeByWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
