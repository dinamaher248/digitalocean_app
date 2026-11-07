<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'category',
        'name',
        'name_ar',
        'name_en',
        'sort_order',
        'is_active',
        'service_radius_km',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'service_radius_km' => 'float',
    ];

    public const CATEGORY_QUICK = 'quick_trip';
    public const CATEGORY_GOODS = 'goods';

    public function scopeForCategory($query, ?string $category)
    {
        if ($category === null || $category === '' || $category === 'all') {
            return $query;
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'category')) {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_GOODS => 'نقل بضائع',
            default => 'مشوار سريع',
        };
    }

    public function scopeActive($query)
    {
        if (Schema::hasColumn($this->getTable(), 'is_active')) {
            return $query->where('is_active', 1);
        }

        return $query;
    }

    public static function slugKey(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $slug = strtolower(str_replace([' ', '-'], '_', $raw));

        return $slug !== '' ? $slug : null;
    }

    public static function normalizeKey(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        return self::slugKey($raw) ?? $raw;
    }

    /** أسماء بديلة معروفة لكل وسيلة نقل، عشان الفلتر ما يفشلش باختلاف صيغة الكتابة. */
    public static function aliasMap(): array
    {
        return [
            'tricycle' => ['toktok', 'tok_tok', 'tuk_tuk', 'tuktok', 'tuktuk', 'توكتوك'],
            'motorcycle' => ['motor', 'motorbike', 'bike', 'موتوسيكل'],
            'car' => ['taxi', 'sedan', 'سيارة'],
        ];
    }

    public static function matchingKeys(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $raw = trim($raw);
        $slug = self::slugKey($raw);
        $keys = [$raw];
        if ($slug) {
            $keys[] = $slug;
        }

        $family = ['schotr', 'scooter', 'scootr', 'skotr'];
        $looksScooter = ($slug && in_array($slug, $family, true))
            || str_contains($raw, 'سكوتر');
        if ($looksScooter) {
            $keys = array_merge($keys, ['Schotr', 'schotr', 'scooter', 'Scooter', 'سكوتر']);
        }

        foreach (self::aliasMap() as $canonical => $aliases) {
            $group = array_merge([$canonical], $aliases);
            $groupSlugs = array_filter(array_map([self::class, 'slugKey'], $group));
            if ($slug !== null && in_array($slug, $groupSlugs, true)) {
                $keys = array_merge($keys, $group, $groupSlugs);
                break;
            }
        }

        return array_values(array_unique(array_filter($keys, fn ($k) => $k !== null && $k !== '')));
    }

    public static function resolveActiveKey(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $raw = trim($raw);
        $slug = self::slugKey($raw);

        if (!Schema::hasTable((new static)->getTable())) {
            return self::normalizeKey($raw) ?? $raw;
        }

        $query = self::query();
        if (Schema::hasColumn((new static)->getTable(), 'is_active')) {
            $query->where('is_active', 1);
        }
        $rows = $query->get(['key']);

        foreach ($rows as $row) {
            $dbKey = trim((string) $row->key);
            if ($dbKey !== '' && strcasecmp($dbKey, $raw) === 0) {
                return $dbKey;
            }
        }

        if ($slug) {
            foreach ($rows as $row) {
                $dbKey = trim((string) $row->key);
                if ($dbKey !== '' && self::slugKey($dbKey) === $slug) {
                    return $dbKey;
                }
            }
        }

        $incoming = array_map('strtolower', self::matchingKeys($raw));
        foreach ($rows as $row) {
            $dbKey = trim((string) $row->key);
            $stored = array_map('strtolower', self::matchingKeys($dbKey));
            if ($incoming !== [] && $stored !== [] && array_intersect($incoming, $stored) !== []) {
                return $dbKey;
            }
        }

        return self::normalizeKey($raw) ?? $raw;
    }

    public function displayName(): string
    {
        if (!empty($this->name_ar)) {
            return (string) $this->name_ar;
        }

        if (!empty($this->name)) {
            return (string) $this->name;
        }

        if (!empty($this->name_en)) {
            return (string) $this->name_en;
        }

        return (string) ($this->key ?? '');
    }
}
