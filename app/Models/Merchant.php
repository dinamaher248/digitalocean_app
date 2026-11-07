<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    protected $fillable = [
        'category_id',
        'owner_user_id',
        'name',
        'image',
        'address',
        'lat',
        'lng',
        'phone',
        'working_hours',
        'is_open',
        'pay_cash',
        'pay_insta',
        'pay_on_delivery',
        'pay_wallet',
        'wallet_number',
        'wallet_owner_name',
        'wallet_type',
        'is_active',
        'avg_rating',
        'ratings_count',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'working_hours' => 'array',
        'is_open' => 'boolean',
        'pay_cash' => 'boolean',
        'pay_insta' => 'boolean',
        'pay_on_delivery' => 'boolean',
        'pay_wallet' => 'boolean',
        'is_active' => 'boolean',
        'avg_rating' => 'float',
        'ratings_count' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(MerchantProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }
}
