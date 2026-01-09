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
        'invoice_number',
        'user_id',
        'shift_id',
        'subtotal',
        'discount_amount',
        'discount_id',
        'total',
        'paid_amount',
        'change_amount',
        'payment_method',
        'notes',
        'synced_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'synced_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->invoice_number)) {
                $transaction->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'INV-' . $date . '-';

        $lastTransaction = self::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->invoice_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Get the user (kasir) who made this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship
     */
    public function kasir(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the shift for this transaction
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the discount applied to this transaction
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Get items in this transaction
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Get stock histories for this transaction
     */
    public function stockHistories(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }

    /**
     * Scope for today's transactions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for transactions in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for unsynced transactions
     */
    public function scopeUnsynced($query)
    {
        return $query->whereNull('synced_at');
    }

    /**
     * Mark as synced
     */
    public function markAsSynced(): void
    {
        $this->update(['synced_at' => now()]);
    }

    /**
     * Get total items count
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('qty');
    }

    /**
     * Get profit from this transaction
     */
    public function getProfitAttribute(): float
    {
        return $this->items->sum(function ($item) {
            $product = $item->product;
            if (!$product) return 0;

            $costPrice = $product->buy_price * $item->qty;
            return $item->subtotal - $costPrice;
        });
    }
}
