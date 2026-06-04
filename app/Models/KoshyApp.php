<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class KoshyApp extends Model
{
    use  HasFactory;

    protected $table = 'koshyapp';

    protected $fillable = [ 
        'id',
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'youtube',
        'phone',
        'created_at',
  
    ];

    protected $hidden = ['updated_at'];
}
