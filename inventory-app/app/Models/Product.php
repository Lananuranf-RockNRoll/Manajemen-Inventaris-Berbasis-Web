<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
            'list_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getProfitMarginAttribute(): float
    {
        $listPrice = (float) $this->list_price;
        $standardCost = (float) $this->standard_cost;

        return round($listPrice - $standardCost, 2);
    }

    public function getProfitPercentageAttribute(): float
    {
        $standardCost = (float) $this->standard_cost;

        if ($standardCost === 0.0) {
            return 0.0;
        }

        return round(($this->profit_margin / $standardCost) * 100, 2);
    }

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $keyword = "%{$term}%";

        return $query->where(function (Builder $subQuery) use ($keyword): void {
            $subQuery->where('name', 'LIKE', $keyword)
                ->orWhere('sku', 'LIKE', $keyword)
                ->orWhere('description', 'LIKE', $keyword);
        });
    }
}
