<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiddingOffer extends Model
{
    protected $fillable = [
        'bidding_trip_id',
        'driver_id',
        'proposed_price',
        'price',
        'status',
    ];

    protected $casts = [
        'proposed_price' => 'float',
        'price' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (BiddingOffer $offer) {
            $amount = $offer->proposed_price ?? $offer->price;
            if ($amount === null) {
                return;
            }
            // الجدول على السيرفر فيه العمودين، والاتنين مطلوبين.
            $offer->price = (float) $amount;
            $offer->proposed_price = (float) $amount;
        });
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(BiddingTrip::class, 'bidding_trip_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function amount(): float
    {
        return (float) ($this->proposed_price ?? $this->price ?? 0);
    }

    public static function priceAttributes(float $amount): array
    {
        return [
            'price' => $amount,
            'proposed_price' => $amount,
        ];
    }
}
