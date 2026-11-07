<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementRequest extends Model
{
    protected $fillable = [
        'user_id',
        'account_type',
        'amount',
        'payment_proof',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
