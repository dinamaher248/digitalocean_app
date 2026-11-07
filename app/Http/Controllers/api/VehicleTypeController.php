<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class VehicleTypeController extends Controller
{
    public function index(Request $request)
    {
        $table = (new VehicleType())->getTable();

        if (!Schema::hasTable($table)) {
            return response()->json([
                'status_code' => 200,
                'status' => 'success',
                'data' => [],
            ]);
        }

        $query = VehicleType::query()->active();

        $category = $request->query('category');
        if (is_string($category) && $category !== '' && $category !== 'all') {
            $query->forCategory($category);
        }

        if (Schema::hasColumn($table, 'sort_order')) {
            $query->orderBy('sort_order');
        }

        $query->orderBy('id');

        $columns = array_values(array_filter(
            ['id', 'key', 'category', 'name', 'name_ar', 'name_en', 'sort_order', 'is_active', 'service_radius_km'],
            fn ($column) => Schema::hasColumn($table, $column)
        ));

        $types = $query->get($columns)->map(function (VehicleType $type) {
            $category = $type->category ?? VehicleType::CATEGORY_QUICK;
            return [
                'id' => $type->id,
                'key' => $type->key,
                'category' => $category,
                'category_label' => $category === VehicleType::CATEGORY_GOODS
                    ? 'نقل بضائع'
                    : 'مشوار سريع',
                'name' => $type->displayName(),
                'name_ar' => $type->name_ar ?? $type->name ?? $type->key,
                'name_en' => $type->name_en,
                'sort_order' => $type->sort_order ?? 0,
                'service_radius_km' => (float) ($type->service_radius_km ?? 7),
            ];
        })->values();

        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'data' => $types,
        ], 200);
    }
}
