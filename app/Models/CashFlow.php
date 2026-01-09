<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_id',
        'type',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the user who created this cash flow
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Scope for cash in
     */
    public function scopeCashIn($query)
    {
        return $query->where('type', 'in');
    }

    /**
     * Scope for cash out
     */
    public function scopeCashOut($query)
    {
        return $query->where('type', 'out');
    }

    /**
     * Scope for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Check if this is cash in
     */
    public function isCashIn(): bool
    {
        return $this->type === 'in';
    }

    /**
     * Check if this is cash out
     */
    public function isCashOut(): bool
    {
        return $this->type === 'out';
    }

    /**
     * Get signed amount (positive for in, negative for out)
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->type === 'in' ? $this->amount : -$this->amount;
    }
}
