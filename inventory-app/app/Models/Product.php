<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'standard_cost',
        'list_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'standard_cost' => 'decimal:2',
            'list_price'    => 'decimal:2',
            'is_active'     => 'boolean',
        ];
    }

    // ── Computed Attributes ─────────────────────────────────────────────────────
    public function getProfitMarginAttribute(): float
    {
        return round((float) $this->list_price - (float) $this->standard_cost, 2);
    }

    public function getProfitPercentageAttribute(): float
    {
        if ((float) $this->standard_cost == 0) {
            return 0;
        }

        return round(($this->profit_margin / (float) $this->standard_cost) * 100, 2);
    }

    // ── Relationships ───────────────────────────────────────────────────────────
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('sku', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%");
        });
    }
}
