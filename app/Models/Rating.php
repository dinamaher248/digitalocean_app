<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = [
        'rater_id',
        'ratee_user_id',
        'ratee_merchant_id',
        'score',
        'comment',
        'context_type',
        'context_id',
    ];

    protected $casts = [
        'score' => 'integer',
        'ratee_user_id' => 'integer',
        'ratee_merchant_id' => 'integer',
        'context_id' => 'integer',
    ];

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function rateeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_user_id');
    }

    public function rateeMerchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'ratee_merchant_id');
    }
}
