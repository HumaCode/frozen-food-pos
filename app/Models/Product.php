<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'barcode',
        'name',
        'image',
        'buy_price',
        'sell_price',
        'stock',
        'unit',
        'min_stock',
        'expired_date',
        'is_active',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'is_active' => 'boolean',
        'expired_date' => 'date',
    ];

    /**
     * Get the category of this product
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get discounts for this product
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    /**
     * Get active discount for this product
     */
    public function activeDiscount(): HasOne
    {
        return $this->hasOne(Discount::class)
            ->where('is_active', true)
            ->where('type', 'product')
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Get wholesale prices for this product
     */
    public function wholesalePrices(): HasMany
    {
        return $this->hasMany(WholesalePrice::class)->orderBy('min_qty');
    }

    /**
     * Get active wholesale prices
     */
    public function activeWholesalePrices(): HasMany
    {
        return $this->hasMany(WholesalePrice::class)
            ->where('is_active', true)
            ->orderBy('min_qty');
    }

    /**
     * Get stock histories
     */
    public function stockHistories(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    /**
     * Scope for expired products
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expired_date')
            ->where('expired_date', '<', now());
    }

    /**
     * Scope for expiring soon (within X days)
     */
    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->whereNotNull('expired_date')
            ->where('expired_date', '>=', now())
            ->where('expired_date', '<=', now()->addDays($days));
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /**
     * Check if product is expired
     */
    public function isExpired(): bool
    {
        return $this->expired_date && $this->expired_date->isPast();
    }

    /**
     * Check if product is expiring soon
     */
    public function isExpiringSoon($days = 7): bool
    {
        return $this->expired_date
            && $this->expired_date->isFuture()
            && $this->expired_date->diffInDays(now()) <= $days;
    }

    /**
     * Get profit margin
     */
    public function getProfitAttribute(): float
    {
        return $this->sell_price - $this->buy_price;
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitPercentageAttribute(): float
    {
        if ($this->buy_price == 0) return 0;
        return (($this->sell_price - $this->buy_price) / $this->buy_price) * 100;
    }

    /**
     * Get wholesale price for given quantity
     */
    public function getWholesalePriceForQty(int $qty): ?float
    {
        $wholesalePrice = $this->activeWholesalePrices()
            ->where('min_qty', '<=', $qty)
            ->orderBy('min_qty', 'desc')
            ->first();

        return $wholesalePrice?->price;
    }

    /**
     * Decrease stock
     */
    public function decreaseStock(int $qty, ?int $userId = null, ?int $transactionId = null): void
    {
        $stockBefore = $this->stock;
        $this->decrement('stock', $qty);

        StockHistory::create([
            'product_id' => $this->id,
            'type' => 'sale',
            'qty' => -$qty,
            'stock_before' => $stockBefore,
            'stock_after' => $this->fresh()->stock,
            'description' => 'Penjualan',
            'user_id' => $userId ?? auth()->id(),
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Increase stock
     */
    public function increaseStock(int $qty, string $description = 'Penambahan stok', ?int $userId = null): void
    {
        $stockBefore = $this->stock;
        $this->increment('stock', $qty);

        StockHistory::create([
            'product_id' => $this->id,
            'type' => 'in',
            'qty' => $qty,
            'stock_before' => $stockBefore,
            'stock_after' => $this->fresh()->stock,
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }
}
