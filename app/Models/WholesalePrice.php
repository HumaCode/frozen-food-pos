<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesalePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'min_qty',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product this wholesale price belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for active wholesale prices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get savings compared to regular price
     */
    public function getSavingsAttribute(): float
    {
        if (!$this->product) return 0;

        return $this->product->sell_price - $this->price;
    }

    /**
     * Get savings percentage
     */
    public function getSavingsPercentageAttribute(): float
    {
        if (!$this->product || $this->product->sell_price == 0) return 0;

        return (($this->product->sell_price - $this->price) / $this->product->sell_price) * 100;
    }
}
