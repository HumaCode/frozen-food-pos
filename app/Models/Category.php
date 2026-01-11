<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty('image')) {
                $oldImage = $model->getOriginal('image');

                if ($oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
        });

        static::deleting(function ($model) {
            // Model pakai SoftDeletes
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                if ($model->isForceDeleting()) {
                    if ($model->image) {
                        Storage::disk('public')->delete($model->image);
                    }
                }
            }
            // Model TIDAK pakai SoftDeletes
            else {
                if ($model->image) {
                    Storage::disk('public')->delete($model->image);
                }
            }
        });
    }

    /**
     * Get products in this category
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get active products in this category
     */
    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('is_active', true);
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered categories
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
