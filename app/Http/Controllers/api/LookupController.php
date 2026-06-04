<?php

// app/Http/Controllers/Api/LookupController.php
namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\VehicleType;

class LookupController extends Controller
{
  public function vehicleTypes()
  {
    return response()->json([
      'status' => 'success',
      'data' => VehicleType::query()
        ->where('is_active',1)
        ->orderBy('sort_order')
        ->get(['id','key','name_ar','name_en'])
    ]);
  }

  public function governorates()
  {
    return response()->json([
      'status' => 'success',
      'data' => Location::query()
        ->active()
        ->type('governorate')
        ->orderBy('sort_order')
        ->get(['id','name_ar'])
    ]);
  }

  public function children(Location $location)
  {
    $rows = Location::query()
      ->active()
      ->where('parent_id', $location->id)
      ->orderBy('sort_order')
      ->withCount('children')
      ->get(['id','type','name_ar','is_primary']);

    $data = $rows->map(fn($x)=>[
      'id' => $x->id,
      'type' => $x->type,
      'name_ar' => $x->name_ar,
      'is_primary' => (bool)$x->is_primary,
      'has_children' => $x->children_count > 0,
    ]);

    return response()->json(['status'=>'success','data'=>$data]);
  }
}
