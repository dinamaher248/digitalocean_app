<?php

namespace App\Models;

// app/Models/VehicleType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleType extends Model
{
  use HasFactory;

  protected $fillable = ['key','name_ar','name_en','sort_order','is_active'];

  protected $casts = [
    'is_active' => 'boolean',
  ];
}
