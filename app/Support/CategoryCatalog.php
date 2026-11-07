<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategoryCatalog
{
    public static function all(): array
    {
        $items = [];
        $seen = [];

        $push = static function ($row, string $table) use (&$items, &$seen) {
            $id = (int) ($row->id ?? 0);
            $name = trim((string) ($row->name ?? ''));
            if ($id <= 0 || $name === '') {
                return;
            }

            $slug = trim((string) ($row->slug ?? ''));
            if ($slug === '') {
                $slug = trim((string) Str::slug($name));
            }
            if ($slug === '') {
                $slug = 'category-'.$id;
            }

            $section = strtolower(trim((string) ($row->hub_section ?? 'orders')));
            if (!in_array($section, ['orders', 'bookings', 'services', 'transport'], true)) {
                $section = 'orders';
            }
            if ($section === 'transport') {
                return;
            }

            $image = $row->icon ?? $row->image ?? null;
            $key = mb_strtolower($name).'|'.$section;
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;

            $items[] = [
                'id' => $id,
                'slug' => $slug,
                'name' => $name,
                'hub_section' => $section,
                'image' => $image,
                'icon' => $image,
                'max_images' => (int) ($row->max_images ?? 10) ?: 10,
                'is_active' => true,
                'source' => $table,
            ];
        };

        self::syncAdminCategoriesIntoServiceTable();

        if (Schema::hasTable('service_categories')) {
            $q = DB::table('service_categories');
            if (Schema::hasColumn('service_categories', 'is_active')) {
                $q->where(function ($w) {
                    $w->where('is_active', 1)
                        ->orWhere('is_active', true)
                        ->orWhereNull('is_active');
                });
            }
            if (Schema::hasColumn('service_categories', 'sort_order')) {
                $q->orderBy('sort_order');
            }
            foreach ($q->orderBy('id')->get() as $row) {
                $push($row, 'service_categories');
            }
        }

        if (Schema::hasTable('categories')) {
            $q = DB::table('categories');
            if (Schema::hasColumn('categories', 'is_active')) {
                $q->where(function ($w) {
                    $w->where('is_active', 1)
                        ->orWhere('is_active', true)
                        ->orWhereNull('is_active');
                });
            }
            if (Schema::hasColumn('categories', 'sort_order')) {
                $q->orderBy('sort_order');
            }
            foreach ($q->orderBy('id')->get() as $row) {
                $push($row, 'categories');
            }
        }

        return $items;
    }

    private static function syncAdminCategoriesIntoServiceTable(): void
    {
        if (!Schema::hasTable('categories') || !Schema::hasTable('service_categories')) {
            return;
        }

        $serviceHasHub = Schema::hasColumn('service_categories', 'hub_section');
        $serviceHasActive = Schema::hasColumn('service_categories', 'is_active');
        $serviceHasSort = Schema::hasColumn('service_categories', 'sort_order');
        $serviceHasIcon = Schema::hasColumn('service_categories', 'icon');
        $serviceHasMax = Schema::hasColumn('service_categories', 'max_images');

        foreach (DB::table('categories')->orderBy('id')->get() as $row) {
            $name = trim((string) ($row->name ?? ''));
            if ($name === '') {
                continue;
            }
            $slug = trim((string) ($row->slug ?? ''));
            if ($slug === '') {
                $slug = trim((string) Str::slug($name));
            }
            if ($slug === '') {
                $slug = 'category-'.(int) $row->id;
            }

            $exists = DB::table('service_categories')
                ->where(function ($q) use ($slug, $name) {
                    $q->where('slug', $slug)->orWhere('name', $name);
                })
                ->exists();
            if ($exists) {
                continue;
            }

            $payload = [
                'name' => $name,
                'slug' => $slug,
            ];
            if ($serviceHasHub && Schema::hasColumn('categories', 'hub_section')) {
                $section = strtolower(trim((string) ($row->hub_section ?? 'orders')));
                $payload['hub_section'] = in_array($section, ['orders', 'bookings', 'services'], true)
                    ? $section
                    : 'orders';
            }
            if ($serviceHasActive) {
                $payload['is_active'] = 1;
            }
            if ($serviceHasSort && Schema::hasColumn('categories', 'sort_order')) {
                $payload['sort_order'] = (int) ($row->sort_order ?? 0);
            }
            if ($serviceHasIcon) {
                $payload['icon'] = $row->icon ?? $row->image ?? null;
            }
            if ($serviceHasMax && Schema::hasColumn('categories', 'max_images')) {
                $payload['max_images'] = (int) ($row->max_images ?? 10) ?: 10;
            }
            if (Schema::hasColumn('service_categories', 'created_at')) {
                $now = now();
                $payload['created_at'] = $now;
                $payload['updated_at'] = $now;
            }

            try {
                DB::table('service_categories')->insert($payload);
            } catch (\Throwable $e) {
            }
        }
    }
}
