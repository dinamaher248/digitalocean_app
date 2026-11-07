<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripRequest extends Model
{
    protected $fillable = [
        'user_id',
        'service_type',
        'vehicle_type',
        'pick_loc',
        'target_loc',
        'pick_lat',
        'pick_lng',
        'target_lat',
        'target_lng',
        'description',
        'contact_phone',
        'notes',
        'distance_km',
        'status',
        'accepted_offer_id',
        'ride_book_id',
    ];

    protected $casts = [
        'pick_lat' => 'float',
        'pick_lng' => 'float',
        'target_lat' => 'float',
        'target_lng' => 'float',
        'distance_km' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(TripOffer::class);
    }

    public function acceptedOffer(): BelongsTo
    {
        return $this->belongsTo(TripOffer::class, 'accepted_offer_id');
    }

    public function rideBook(): BelongsTo
    {
        return $this->belongsTo(RideBook::class, 'ride_book_id');
    }
}
