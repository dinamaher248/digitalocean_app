<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * GET /api/locations
     * يرجّع المحافظات + المناطق التابعة لها من جدول locations
     */
    public function index(Request $request)
    {
        $lang = $request->get('lang', 'ar');

        $governorates = Location::query()
            ->active()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['children' => function ($q) {
                $q->active()->ordered();
            }])
            ->get();

        $result = $governorates->map(function (Location $gov) use ($lang) {
            return [
                'id' => $gov->id,
                'name' => $gov->displayName($lang),
                'areas' => $gov->children->map(function (Location $area) use ($lang) {
                    return [
                        'id' => $area->id,
                        'name' => $area->displayName($lang),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $flatLocations = Location::query()
            ->active()
            ->ordered()
            ->get()
            ->map(function (Location $location) use ($lang) {
                return [
                    'id' => $location->id,
                    'name' => $location->displayName($lang),
                    'parent_id' => $location->parent_id,
                    'is_active' => $location->is_active,
                    'sort_order' => $location->sort_order,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'governorates' => $result,
            'locations' => $flatLocations,
        ], 200);
    }

    public function children($governorateId, Request $request)
    {
        $lang = $request->get('lang', 'ar');

        $gov = Location::active()
            ->whereNull('parent_id')
            ->findOrFail($governorateId);

        $areas = $gov->children()
            ->active()
            ->ordered()
            ->get()
            ->map(function (Location $area) use ($lang) {
                return [
                    'id' => $area->id,
                    'name' => $area->displayName($lang),
                ];
            });

        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'governorate' => [
                'id' => $gov->id,
                'name' => $gov->displayName($lang),
            ],
            'areas' => $areas,
        ]);
    }
}
