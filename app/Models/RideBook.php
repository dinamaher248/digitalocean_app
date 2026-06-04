<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class RideBook extends Model
{
    use  HasFactory;

    protected $table = 'ride_book';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'id',
        'driver_id',
        'user_id',
        'seat_count',
        'amount',
        'msg',
        'pick_loc',
        'target_loc',
        'status',
        'created_at',
  
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['updated_at'];

    public function ride()
    {
        return $this->belongsTo(Ride::class, 'ride_id');
    }

    public function driver()
{
    return $this->belongsTo(User::class, 'driver_id');
}
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // public function user()
    // {
        // return $this->belongsTo(User::class, 'doctor_id');
    // }
}
