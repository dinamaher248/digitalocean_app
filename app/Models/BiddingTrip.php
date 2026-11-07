<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BiddingTrip extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_type',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'description',
        'phone',
        'notes',
        'customer_price',
        'status',
        'final_price',
        'accepted_offer_id',
        'accepted_driver_id',
        'driver_id',
        'ride_book_id',
    ];

    protected $casts = [
        'pickup_lat' => 'float',
        'pickup_lng' => 'float',
        'dropoff_lat' => 'float',
        'dropoff_lng' => 'float',
        'final_price' => 'float',
        'customer_price' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(BiddingOffer::class, 'bidding_trip_id');
    }

    public function acceptedOffer(): BelongsTo
    {
        return $this->belongsTo(BiddingOffer::class, 'accepted_offer_id');
    }

    public static function assignedDriverColumn(): ?string
    {
        $table = (new static)->getTable();
        if (Schema::hasColumn($table, 'accepted_driver_id')) {
            return 'accepted_driver_id';
        }
        if (Schema::hasColumn($table, 'driver_id')) {
            return 'driver_id';
        }

        return null;
    }

    public static function ensureSchema(): void
    {
        $table = (new static)->getTable();
        if (!Schema::hasTable($table) || Schema::hasColumn($table, 'accepted_driver_id')) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('accepted_driver_id')->nullable();
            });
            if (Schema::hasColumn($table, 'driver_id')) {
                DB::table($table)->whereNull('accepted_driver_id')->whereNotNull('driver_id')
                    ->update(['accepted_driver_id' => DB::raw('driver_id')]);
            }
        } catch (\Throwable $e) {
            // جدول قديم بدون صلاحية ALTER — الاستعلامات تستخدم العمود المتاح.
        }
    }

    public function assignedDriverId(): ?int
    {
        $col = self::assignedDriverColumn();
        if ($col === null) {
            return null;
        }
        $value = $this->getAttribute($col);

        return $value !== null ? (int) $value : null;
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, self::assignedDriverColumn() ?: 'accepted_driver_id');
    }
}
