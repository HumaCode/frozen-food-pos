<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, HasPanelShield;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'avatar',
        'email',
        'password',
        'is_active',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty('avatar')) {
                $oldImage = $model->getOriginal('avatar');

                if ($oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
        });

        static::deleting(function ($model) {
            // Model pakai SoftDeletes
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                if ($model->isForceDeleting()) {
                    if ($model->avatar) {
                        Storage::disk('public')->delete($model->avatar);
                    }
                }
            }
            // Model TIDAK pakai SoftDeletes
            else {
                if ($model->avatar) {
                    Storage::disk('public')->delete($model->avatar);
                }
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get transactions for this user
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get cash flows for this user
     */
    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class);
    }

    /**
     * Get stock histories for this user
     */
    public function stockHistories(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }
}
