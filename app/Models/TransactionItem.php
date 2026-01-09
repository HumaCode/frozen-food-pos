<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name',
        'price',
        'qty',
        'discount_per_item',
        'is_wholesale',
        'subtotal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_per_item' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_wholesale' => 'boolean',
    ];

    /**
     * Get the transaction this item belongs to
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate subtotal
     */
    public function calculateSubtotal(): float
    {
        $gross = $this->price * $this->qty;
        $discount = $this->discount_per_item * $this->qty;

        return $gross - $discount;
    }

    /**
     * Get unit price after discount
     */
    public function getNetPriceAttribute(): float
    {
        return $this->price - $this->discount_per_item;
    }

    /**
     * Get total discount for this item
     */
    public function getTotalDiscountAttribute(): float
    {
        return $this->discount_per_item * $this->qty;
    }
}
