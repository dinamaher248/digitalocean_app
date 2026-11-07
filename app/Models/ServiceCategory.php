<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ServiceCategory extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'icon',
        'hub_section',
        'max_images',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_images' => 'integer',
    ];

    public const DEFAULT_MAX_IMAGES = 10;

    /** الحد الأقصى لعدد الصور التي يرفعها صاحب النشاط داخل هذا القسم. */
    public function maxImages(): int
    {
        $value = (int) ($this->max_images ?? 0);

        return $value > 0 ? $value : self::DEFAULT_MAX_IMAGES;
    }

    /** قسم الفوترة: orders | bookings | services */
    public function billingSection(): string
    {
        $section = strtolower(trim((string) ($this->hub_section ?? 'orders')));

        return in_array($section, ['orders', 'bookings', 'services'], true) ? $section : 'orders';
    }

    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class, 'category_id');
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            MerchantProduct::class,
            Merchant::class,
            'category_id',
            'merchant_id'
        );
    }
}
