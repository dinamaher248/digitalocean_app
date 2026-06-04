<?php

namespace App\Models;

// app/Models/Location.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
  use HasFactory;

      const TYPE_GOVERNORATE = 'governorate';
    const TYPE_AREA        = 'area';// العلاقات
    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name_ar');
    }

    // سكوبات عامة
    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }

    public function scopeType($q, string $type)
    {
        return $q->where('type', $type);
    }

    public function scopeGovernorates($q)
    {
        return $q->type(self::TYPE_GOVERNORATE);
    }

    public function scopeAreas($q)
    {
        return $q->type(self::TYPE_AREA);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('name_ar');
    }

    public function scopeRoots($q)
    {
        // لو حبيتي تعاملي أي شجرة عامة مش بس محافظات/مدن
        return $q->whereNull('parent_id');
    }

    /**
     * إرجاع شجرة المحافظات مع المدن التابعة لها
     * لاستخدامها في لوحة التحكم أو الـ API.
     */
    public static function governoratesTree(bool $onlyActive = false)
    {
        $query = self::governorates()
            ->ordered()
            ->with(['children' => function ($q) use ($onlyActive) {
                $q->areas()->ordered();
                if ($onlyActive) {
                    $q->active();
                }
            }]);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }
}
