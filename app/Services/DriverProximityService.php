<?php

namespace App\Services;

use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DriverProximityService
{
    /**
     * Default km used when a vehicle type has no configured radius.
     */
    public const DEFAULT_RADIUS_KM = 7.0;

    public function normalizeVehicleType(?string $vehicleType): string
    {
        $vehicleType = strtolower(trim((string) $vehicleType));

        return match ($vehicleType) {
            'toktok' => 'tricycle',
            default => $vehicleType,
        };
    }

    public function radiusKmForVehicle(?string $vehicleType): float
    {
        $key = $this->normalizeVehicleType($vehicleType);
        if ($key === '' || !Schema::hasTable('vehicle_types')) {
            return self::DEFAULT_RADIUS_KM;
        }

        $query = VehicleType::query()->where('key', $key);
        if (Schema::hasColumn('vehicle_types', 'is_active')) {
            $query->where('is_active', 1);
        }

        $type = $query->first();
        if (!$type) {
            return self::DEFAULT_RADIUS_KM;
        }

        if (!Schema::hasColumn('vehicle_types', 'service_radius_km')) {
            return self::DEFAULT_RADIUS_KM;
        }

        $radius = (float) ($type->service_radius_km ?? self::DEFAULT_RADIUS_KM);
        return $radius > 0 ? $radius : self::DEFAULT_RADIUS_KM;
    }

    public function distanceKm(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $earthRadius = 6371;
        $latFrom = deg2rad((float) $lat1);
        $lonFrom = deg2rad((float) $lng1);
        $latTo = deg2rad((float) $lat2);
        $lonTo = deg2rad((float) $lng2);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return round($angle * $earthRadius, 2);
    }

    public function isWithinRadius(
        ?float $originLat,
        ?float $originLng,
        ?float $driverLat,
        ?float $driverLng,
        float $radiusKm
    ): bool {
        // If pickup or driver location is missing, keep legacy behavior
        // (still notify/show) so the system does not go silent.
        if ($originLat === null || $originLng === null) {
            return true;
        }
        if ($driverLat === null || $driverLng === null) {
            return true;
        }

        $distance = $this->distanceKm($originLat, $originLng, $driverLat, $driverLng);
        if ($distance === null) {
            return true;
        }

        return $distance <= $radiusKm;
    }

    /**
     * Online drivers of a vehicle type within radius of a pickup point.
     *
     * @return Collection<int, User>
     */
    public function nearbyOnlineDrivers(
        ?string $vehicleType,
        ?float $pickupLat,
        ?float $pickupLng,
        int $limit = 80,
        bool $requireFcm = true
    ): Collection {
        $normalized = $this->normalizeVehicleType($vehicleType);
        $radiusKm = $this->radiusKmForVehicle($normalized);

        $query = User::query()
            ->where('title', 'driver')
            ->where('is_online', true)
            ->limit($limit);

        if ($requireFcm) {
            $query->whereNotNull('fcm_token')->where('fcm_token', '!=', '');
        }

        $drivers = $query->get()->filter(function (User $driver) use ($normalized, $pickupLat, $pickupLng, $radiusKm) {
            if ($this->normalizeVehicleType($driver->vehicle_type) !== $normalized) {
                return false;
            }

            $driverLat = Schema::hasColumn('users', 'current_lat')
                ? ($driver->current_lat !== null ? (float) $driver->current_lat : null)
                : null;
            $driverLng = Schema::hasColumn('users', 'current_lng')
                ? ($driver->current_lng !== null ? (float) $driver->current_lng : null)
                : null;

            return $this->isWithinRadius(
                $pickupLat,
                $pickupLng,
                $driverLat,
                $driverLng,
                $radiusKm
            );
        })->values();

        return $drivers;
    }

    public function driverSeesPickup(User $driver, ?float $pickupLat, ?float $pickupLng, ?string $vehicleType = null): bool
    {
        $type = $vehicleType ?: $driver->vehicle_type;
        $radiusKm = $this->radiusKmForVehicle($type);

        $driverLat = Schema::hasColumn('users', 'current_lat')
            ? ($driver->current_lat !== null ? (float) $driver->current_lat : null)
            : null;
        $driverLng = Schema::hasColumn('users', 'current_lng')
            ? ($driver->current_lng !== null ? (float) $driver->current_lng : null)
            : null;

        return $this->isWithinRadius($pickupLat, $pickupLng, $driverLat, $driverLng, $radiusKm);
    }
}
