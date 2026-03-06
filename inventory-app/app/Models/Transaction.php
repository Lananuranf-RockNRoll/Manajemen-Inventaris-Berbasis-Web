<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'employee_id',
        'warehouse_id',
        'status',
        'order_date',
        'shipped_date',
        'total_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date'   => 'date',
            'shipped_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    // ── Boot ────────────────────────────────────────────────────────────────────
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Transaction $transaction) {
            if (empty($transaction->order_number)) {
                $transaction->order_number = 'ORD-' . strtoupper(uniqid());
            }

            if (empty($transaction->order_date)) {
                $transaction->order_date = now()->toDateString();
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────────────────
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────────
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('order_date', [$from, $to]);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
