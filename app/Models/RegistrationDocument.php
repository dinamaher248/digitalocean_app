<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RegistrationDocument extends Model
{
    protected $fillable = [
        'key',
        'label',
        'field_type',
        'user_type',
        'vehicle_type_keys',
        'vendor_hub_sections',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'vehicle_type_keys' => 'array',
        'vendor_hub_sections' => 'array',
    ];

    public function vehicleTypeKeys(): array
    {
        if (!Schema::hasColumn($this->getTable(), 'vehicle_type_keys')) {
            return [];
        }

        $keys = $this->vehicle_type_keys;
        if (is_string($keys) && trim($keys) !== '') {
            $decoded = json_decode($keys, true);
            $keys = is_array($decoded) ? $decoded : preg_split('/[,،]+/u', $keys);
        }
        if (!is_array($keys)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($key) => trim((string) $key),
            $keys
        )));
    }

    public function appliesToRole(string $role): bool
    {
        $role = strtolower(trim($role));
        if (in_array($role, ['merchant', 'vendors'], true)) {
            $role = 'vendor';
        }

        $audience = strtolower(trim((string) ($this->user_type ?? 'both')));
        if ($audience === '' || $audience === 'both') {
            return true;
        }

        return $audience === $role;
    }

    public function vendorHubSections(): array
    {
        if (!Schema::hasColumn($this->getTable(), 'vendor_hub_sections')) {
            return [];
        }

        $keys = $this->vendor_hub_sections;
        if (is_string($keys) && trim($keys) !== '') {
            $decoded = json_decode($keys, true);
            $keys = is_array($decoded) ? $decoded : preg_split('/[,،]+/u', $keys);
        }
        if (!is_array($keys)) {
            return [];
        }

        $allowed = ['orders', 'bookings', 'services'];

        return array_values(array_unique(array_filter(array_map(
            function ($key) use ($allowed) {
                $key = strtolower(trim((string) $key));

                return in_array($key, $allowed, true) ? $key : null;
            },
            $keys
        ))));
    }

    public function appliesToVendorHub(?string $hubSection): bool
    {
        $audience = strtolower(trim((string) ($this->user_type ?? 'both')));
        if ($audience === 'driver') {
            return false;
        }

        $hubs = $this->vendorHubSections();
        if ($hubs === []) {
            return true;
        }
        if ($hubSection === null || trim($hubSection) === '') {
            return true;
        }

        return in_array(strtolower(trim($hubSection)), $hubs, true);
    }

    public function appliesToVehicleType(?string $vehicleTypeKey): bool
    {
        $audience = strtolower(trim((string) ($this->user_type ?? 'both')));
        if ($audience === 'vendor') {
            return false;
        }

        $keys = $this->vehicleTypeKeys();
        if ($keys === []) {
            return true;
        }

        if ($vehicleTypeKey === null || trim($vehicleTypeKey) === '') {
            return true;
        }

        $incomingRaw = trim($vehicleTypeKey);
        $incomingResolved = class_exists(VehicleType::class) && method_exists(VehicleType::class, 'resolveActiveKey')
            ? (VehicleType::resolveActiveKey($incomingRaw) ?? $incomingRaw)
            : $incomingRaw;
        $incomingMatches = class_exists(VehicleType::class) && method_exists(VehicleType::class, 'matchingKeys')
            ? array_map('strtolower', VehicleType::matchingKeys($incomingRaw))
            : [strtolower($incomingRaw)];

        foreach ($keys as $stored) {
            $stored = trim((string) $stored);
            if ($stored === '') {
                continue;
            }
            if (strcasecmp($stored, $incomingRaw) === 0
                || strcasecmp($stored, (string) $incomingResolved) === 0) {
                return true;
            }
            $storedResolved = VehicleType::resolveActiveKey($stored) ?? $stored;
            if (strcasecmp((string) $storedResolved, (string) $incomingResolved) === 0) {
                return true;
            }
            $storedMatches = array_map('strtolower', VehicleType::matchingKeys($stored));
            if ($incomingMatches !== [] && $storedMatches !== []
                && array_intersect($incomingMatches, $storedMatches) !== []) {
                return true;
            }
        }

        return false;
    }

    public function audienceLabel(): string
    {
        return match ($this->user_type) {
            'vendor' => 'صاحب نشاط',
            'driver' => 'سائق',
            default => 'الكل',
        };
    }

    public function vehicleTypeLabels(): array
    {
        $keys = $this->vehicleTypeKeys();
        if ($keys === [] || !Schema::hasTable('vehicle_types')) {
            return [];
        }

        $labels = [];
        foreach ($keys as $key) {
            $row = VehicleType::query()->where('key', $key)->first();
            $labels[] = $row?->name_ar ?: $row?->name_en ?: $row?->name ?: $key;
        }

        return $labels;
    }

    public static function forRegistrationApi(
        string $role,
        ?string $vehicleTypeKey = null,
        ?string $hubSection = null
    ): array {
        $role = strtolower(trim($role));
        if (in_array($role, ['merchant', 'vendors'], true)) {
            $role = 'vendor';
        }

        if (!Schema::hasTable((new static)->getTable())) {
            return self::fallbackRegistrationDocuments($role);
        }

        $q = static::query();
        if (Schema::hasColumn((new static)->getTable(), 'sort_order')) {
            $q->orderBy('sort_order');
        }
        $q->orderBy('id');
        if (Schema::hasColumn((new static)->getTable(), 'is_active')) {
            $q->where(function ($w) {
                $w->where('is_active', 1)->orWhere('is_active', true);
            });
        }

        $matched = [];

        foreach ($q->get() as $doc) {
            if (method_exists($doc, 'appliesToRole') && !$doc->appliesToRole($role)) {
                continue;
            }

            if ($role === 'driver'
                && method_exists($doc, 'appliesToVehicleType')
                && !$doc->appliesToVehicleType($vehicleTypeKey)) {
                continue;
            }

            if ($role === 'vendor'
                && method_exists($doc, 'appliesToVendorHub')
                && !$doc->appliesToVendorHub($hubSection)) {
                continue;
            }

            $name = trim((string) ($doc->label ?? $doc->name ?? ''));
            if ($name === '') {
                continue;
            }

            $matched[] = [
                'id' => (int) $doc->id,
                'name' => $name,
                'label' => $name,
                'title' => $name,
                'key' => $doc->key ?? null,
                'audience' => $doc->user_type === 'merchant' ? 'vendor' : ($doc->user_type ?? 'both'),
                'user_type' => $doc->user_type ?? 'both',
                'vehicle_type_keys' => method_exists($doc, 'vehicleTypeKeys') ? $doc->vehicleTypeKeys() : [],
                'vendor_hub_sections' => method_exists($doc, 'vendorHubSections') ? $doc->vendorHubSections() : [],
                'is_required' => (bool) ($doc->is_required ?? true),
                'is_active' => (bool) ($doc->is_active ?? true),
            ];
        }

        return $matched;
    }

    public static function fallbackRegistrationDocuments(string $role): array
    {
        $role = strtolower(trim($role));
        if ($role === 'driver') {
            return [
                [
                    'id' => 1900001,
                    'name' => 'صورة شخصية',
                    'label' => 'صورة شخصية',
                    'audience' => 'driver',
                    'user_type' => 'driver',
                    'vehicle_type_keys' => [],
                    'is_required' => true,
                    'is_active' => true,
                ],
                [
                    'id' => 1900002,
                    'name' => 'صورة البطاقة القومية',
                    'label' => 'صورة البطاقة القومية',
                    'audience' => 'driver',
                    'user_type' => 'driver',
                    'vehicle_type_keys' => [],
                    'is_required' => true,
                    'is_active' => true,
                ],
            ];
        }

        if (in_array($role, ['vendor', 'merchant'], true)) {
            return [
                [
                    'id' => 1900011,
                    'name' => 'صورة النشاط',
                    'label' => 'صورة النشاط',
                    'audience' => 'vendor',
                    'user_type' => 'vendor',
                    'vehicle_type_keys' => [],
                    'is_required' => true,
                    'is_active' => true,
                ],
            ];
        }

        return [];
    }
}
