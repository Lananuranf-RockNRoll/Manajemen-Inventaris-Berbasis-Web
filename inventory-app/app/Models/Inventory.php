<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    public function getQtyAvailableAttribute(): int
    {
        return max(0, $this->qty_on_hand - $this->qty_reserved);
    }

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
    public function scopeLowStock($query)
    {
        return $query->whereRaw('(qty_on_hand - qty_reserved) <= min_stock');
    }

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
