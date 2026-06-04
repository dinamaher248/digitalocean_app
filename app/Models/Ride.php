<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Ride extends Model
{
    use  HasFactory;

    protected $table = 'ride';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'id',
        'car_id',
        'user_id',
        'departure_date',
        'departure_time',
        'start_lat',
        'start_lng',
        'start_name',
        'dest_lat',
        'dest_lng',
        'dest_name',
        'seats',
        'price',
        'description',
        'image',
        'status',
        'created_at',
  
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['updated_at'];

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function rideBook()
    {
        return $this->hasMany(RideBook::class, 'ride_id');
    }
    // public function user()
    // {
        // return $this->belongsTo(User::class, 'doctor_id');
    // }
}
