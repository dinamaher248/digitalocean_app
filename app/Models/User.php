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
    'admin_permissions',
    'nid_image',
    'license_image',
    'image',
    'vehicle_type',
    'phone',
    'area',
    'is_paid',
    'is_approved',
    'is_suspended',
    'subscription_end_date',
    'is_online',
    'current_lat',
    'current_lng',
    'location_updated_at',
    'governorate',
    'governorate_id',
    'area_id',
    'location_id',
    'fcm_token',
    'dues_balance',
    'commission_rate',
    'vendor_billing_mode',
    'avg_rating',
    'ratings_count',
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
    'is_suspended' => 'boolean',
    'admin_permissions' => 'array',
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

public function ownedMerchant()
{
  return $this->hasOne(Merchant::class, 'owner_user_id');
}

  /**
   * بحث لوحة الإدارة: رقم المستخدم أو الهاتف أو الاسم أو البريد.
   */
  public function scopeAdminSearch($query, ?string $term)
  {
    $term = trim((string) $term);
    if ($term === '') {
      return $query;
    }

    $idTerm = ltrim($term, '#');
    if (ctype_digit($idTerm) && strlen($idTerm) <= 9) {
      return $query->where($this->getTable().'.id', (int) $idTerm);
    }

    $digits = preg_replace('/\D+/', '', $term) ?: $term;

    return $query->where(function ($q) use ($term, $digits) {
      $q->where('phone', 'like', '%'.$term.'%')
        ->orWhere('name', 'like', '%'.$term.'%')
        ->orWhere('email', 'like', '%'.$term.'%');
      if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'username')) {
        $q->orWhere('username', 'like', '%'.$term.'%');
      }
      if ($digits !== $term && strlen($digits) >= 3) {
        $q->orWhere('phone', 'like', '%'.$digits.'%');
      }
    });
  }

}
