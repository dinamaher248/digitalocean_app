<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
  use HasApiTokens, HasFactory, Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'username',
    'email',
    'password',
    'type',
    'title',
    'coins',
    'is_admin',
    'nid_image',
    'license_image',
    'image',
    'vehicle_type',
    'phone',
    'area',
    'is_paid',
    'is_approved',
    'subscription_end_date',
    'is_online',
    'governorate',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
  ];

  public function rideBooks()
{
    return $this->hasMany(RideBook::class, 'driver_id');
}

  public function subscription()
  {
    return $this->hasOne(Subscription::class, 'user_id')->where('status', 'approved');
  }
  public function getJWTIdentifier()
  {
    return $this->getKey();
  }

  public function getJWTCustomClaims()
  {
    return [
      'email' => $this->email,
      'name' => $this->name
    ];
  }
  public function isAdmin()
  {
    return $this->is_admin;
  }


  // app/Models/User.php
public function vehicleType()
{
  return $this->belongsTo(\App\Models\VehicleType::class, 'vehicle_type_id');
}

public function governorate()
{
  return $this->belongsTo(\App\Models\Location::class, 'governorate_id');
}

public function locality()
{
  return $this->belongsTo(\App\Models\Location::class, 'locality_id');
}

public function area()
{
  return $this->belongsTo(\App\Models\Location::class, 'area_id');
}

}
