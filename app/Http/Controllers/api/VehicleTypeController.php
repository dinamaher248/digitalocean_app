<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;

class VehicleTypeController extends Controller
{
    public function index()
    {
        $types = VehicleType::where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'key', 'name_ar', 'name_en', 'sort_order']);

        return response()->json([
            'status_code' => 200,
            'status'      => 'success',
            'data'        => $types,
        ], 200);
    }
}
