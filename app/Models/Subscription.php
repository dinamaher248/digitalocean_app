<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Subscription extends Model
{
    use  HasFactory;

    protected $table = 'subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'id',
        'user_id',
        'plan_id',
        'payment_image',
        'status',      // pending, approved, rejected
        'start_date',
        'end_date',
        'created_at',
  
    ];
 protected $dates = [
        'start_date',
        'end_date',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['updated_at'];

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
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
