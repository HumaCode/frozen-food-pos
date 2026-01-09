<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'logo',
        'receipt_footer',
        'printer_size',
    ];

    /**
     * Get the store settings (singleton pattern)
     */
    public static function getSettings(): ?self
    {
        return self::first();
    }

    /**
     * Update or create store settings
     */
    public static function updateSettings(array $data): self
    {
        $store = self::first();

        if ($store) {
            $store->update($data);
        } else {
            $store = self::create($data);
        }

        return $store;
    }
}
