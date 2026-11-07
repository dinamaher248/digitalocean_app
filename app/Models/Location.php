<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Location extends Model
{
    use HasFactory;

    const TYPE_GOVERNORATE = 'governorate';
    const TYPE_AREA = 'area';

    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'parent_id',
        'type',
        'is_active',
        'sort_order',
    ];

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id')->ordered();
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }

    public function scopeType($q, string $type)
    {
        if (!self::hasTypeColumn()) {
            return $q;
        }

        return $q->where('type', $type);
    }

    public function scopeGovernorates($q)
    {
        if (self::hasTypeColumn()) {
            return $q->type(self::TYPE_GOVERNORATE);
        }

        return $q->whereNull('parent_id');
    }

    public function scopeAreas($q)
    {
        if (self::hasTypeColumn()) {
            return $q->type(self::TYPE_AREA);
        }

        return $q->whereNotNull('parent_id');
    }

    public function scopeOrdered($q)
    {
        if (Schema::hasColumn((new self())->getTable(), 'sort_order')) {
            $q->orderBy('sort_order');
        }

        $table = (new self())->getTable();
        if (Schema::hasColumn($table, 'name_ar')) {
            return $q->orderBy('name_ar');
        }
        if (Schema::hasColumn($table, 'name')) {
            return $q->orderBy('name');
        }

        return $q;
    }

    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }

    public function displayName(string $lang = 'ar'): string
    {
        if (!empty($this->name)) {
            return (string) $this->name;
        }

        if ($lang === 'en' && !empty($this->name_en)) {
            return (string) $this->name_en;
        }

        if (!empty($this->name_ar)) {
            return (string) $this->name_ar;
        }

        return (string) ($this->name_en ?? '');
    }

    public static function governoratesTree(bool $onlyActive = false)
    {
        $query = self::roots()->ordered()->with(['children' => function ($q) use ($onlyActive) {
            if ($onlyActive) {
                $q->active();
            }
            $q->ordered();
        }]);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    protected static function hasTypeColumn(): bool
    {
        static $hasTypeColumn = null;

        if ($hasTypeColumn === null) {
            $hasTypeColumn = Schema::hasColumn((new self())->getTable(), 'type');
        }

        return $hasTypeColumn;
    }
}
