<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'qty',
        'stock_before',
        'stock_after',
        'description',
        'user_id',
        'transaction_id',
    ];

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction (if applicable)
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'in' => 'Masuk',
            'out' => 'Keluar',
            'adjustment' => 'Penyesuaian',
            'sale' => 'Penjualan',
            default => $this->type,
        };
    }

    /**
     * Get type color for UI
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'in' => 'success',
            'out' => 'danger',
            'adjustment' => 'warning',
            'sale' => 'info',
            default => 'secondary',
        };
    }
}
