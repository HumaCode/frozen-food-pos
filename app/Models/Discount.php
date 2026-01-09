<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'discount_type',
        'value',
        'product_id',
        'min_purchase',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the product this discount belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for active discounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope for product discounts
     */
    public function scopeProductDiscount($query)
    {
        return $query->where('type', 'product');
    }

    /**
     * Scope for total discounts
     */
    public function scopeTotalDiscount($query)
    {
        return $query->where('type', 'total');
    }

    /**
     * Check if discount is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) return false;

        $now = now();

        if ($this->start_date && $this->start_date->isAfter($now)) {
            return false;
        }

        if ($this->end_date && $this->end_date->isBefore($now)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $price, int $qty = 1): float
    {
        if ($this->discount_type === 'percentage') {
            return ($price * $qty) * ($this->value / 100);
        }

        return $this->value * $qty;
    }

    /**
     * Get formatted discount value
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return $this->value . '%';
        }

        return 'Rp ' . number_format($this->value, 0, ',', '.');
    }
}
