<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    /**
     * Get transactions for this shift
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get cash flows for this shift
     */
    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class);
    }

    /**
     * Scope for active shifts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute(): string
    {
        $start = $this->start_time instanceof \DateTime
            ? $this->start_time->format('H:i')
            : $this->start_time;
        $end = $this->end_time instanceof \DateTime
            ? $this->end_time->format('H:i')
            : $this->end_time;

        return $start . ' - ' . $end;
    }

    /**
     * Check if current time is within this shift
     */
    public function isCurrentShift(): bool
    {
        $now = now()->format('H:i:s');
        $start = $this->start_time instanceof \DateTime
            ? $this->start_time->format('H:i:s')
            : $this->start_time;
        $end = $this->end_time instanceof \DateTime
            ? $this->end_time->format('H:i:s')
            : $this->end_time;

        // Handle overnight shifts (e.g., 22:00 - 06:00)
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }
}
