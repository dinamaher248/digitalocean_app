<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * GET /api/locations
     * يرجّع المحافظات + المدن/الأحياء تحت كل محافظة
     *
     * Optional: ?lang=ar | en
     */
    public function index(Request $request)
    {
        $lang = $request->get('lang', 'ar'); // لو عايز تبدّل بين name_ar / name_en

        // نجيب كل المحافظات (parent_id = null) + الأطفال (المدن/الأحياء)
        $governorates = Location::query()
            ->active()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['children' => function ($q) {
                $q->active()->orderBy('sort_order');
            }])
            ->get();

        // نجهّز الـ JSON
        $result = $governorates->map(function (Location $gov) use ($lang) {
            $govName = $lang === 'en' && $gov->name_en
                ? $gov->name_en
                : $gov->name_ar;

            return [
                'id'       => $gov->id,
                'name'     => $govName,
                'name_ar'  => $gov->name_ar,
                'name_en'  => $gov->name_en,
                'areas'    => $gov->children->map(function (Location $area) use ($lang) {
                    $areaName = $lang === 'en' && $area->name_en
                        ? $area->name_en
                        : $area->name_ar;

                    return [
                        'id'       => $area->id,
                        'name'     => $areaName,
                        'name_ar'  => $area->name_ar,
                        'name_en'  => $area->name_en,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'status_code'  => 200,
            'status'       => 'success',
            'governorates' => $result,
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
        ->orderBy('sort_order')
        ->get()
        ->map(function (Location $area) use ($lang) {
            $areaName = $lang === 'en' && $area->name_en
                ? $area->name_en
                : $area->name_ar;

            return [
                'id'       => $area->id,
                'name'     => $areaName,
                'name_ar'  => $area->name_ar,
                'name_en'  => $area->name_en,
            ];
        });

    return response()->json([
        'status_code' => 200,
        'status'      => 'success',
        'governorate' => [
            'id'   => $gov->id,
            'name' => $lang === 'en' && $gov->name_en ? $gov->name_en : $gov->name_ar,
        ],
        'areas'       => $areas,
    ]);
}

}
