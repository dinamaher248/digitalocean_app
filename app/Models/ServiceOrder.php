<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class ServiceOrder extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_id',
        'store_id',
        'order_type',
        'product_id',
        'product_name',
        'product_price',
        'product_description',
        'product_total',
        'custom_request_text',
        'custom_request_image',
        'quantity',
        'total_price',
        'merchant_offer_price',
        'merchant_offer_notes',
        'notes',
        'customer_contact_phone',
        'delivery_address',
        'delivery_details',
        'delivery_lat',
        'delivery_lng',
        'fulfillment_type',
        'payment_method',
        'payment_proof',
        'wallet_receipt',
        'status',
        'driver_id',
        'invited_driver_id',
        'ride_book_id',
        'accepted_delivery_offer_id',
        'delivery_fee',
        'commission_amount',
        'commission_applied',
    ];

    /** يحفظ الطلب حسب أعمدة الجدول الفعلية (sonic: store_id / motorent_back: merchant_id). */
    public static function createCompatible(array $attrs): self
    {
        $table = (new static)->getTable();

        if (!empty($attrs['merchant_id']) && empty($attrs['store_id'])) {
            $attrs['store_id'] = $attrs['merchant_id'];
        }
        if (!empty($attrs['store_id']) && empty($attrs['merchant_id'])) {
            $attrs['merchant_id'] = $attrs['store_id'];
        }
        if (!isset($attrs['product_total']) && isset($attrs['total_price'])) {
            $attrs['product_total'] = $attrs['total_price'];
        }
        if (!isset($attrs['total_price']) && isset($attrs['product_total'])) {
            $attrs['total_price'] = $attrs['product_total'];
        }
        if (empty($attrs['wallet_receipt']) && !empty($attrs['payment_proof'])) {
            $attrs['wallet_receipt'] = $attrs['payment_proof'];
        }
        if (empty($attrs['payment_proof']) && !empty($attrs['wallet_receipt'])) {
            $attrs['payment_proof'] = $attrs['wallet_receipt'];
        }
        if (empty($attrs['notes']) && !empty($attrs['delivery_details'])) {
            $attrs['notes'] = $attrs['delivery_details'];
        }

        $payment = strtolower(trim((string) ($attrs['payment_method'] ?? 'cash')));
        if (in_array($payment, ['on_delivery', 'cod', 'cash_on_delivery', 'pay_on_delivery'], true)) {
            $payment = 'cash';
        }
        if ($payment === 'e_wallet') {
            $payment = 'wallet';
        }
        $attrs['payment_method'] = $payment;

        $status = (string) ($attrs['status'] ?? 'new');
        if (in_array($status, ['awaiting_payment_review', 'awaiting_merchant_offer'], true)
            && !Schema::hasColumn($table, 'merchant_id')) {
            $attrs['status'] = 'new';
        }

        $filtered = [];
        foreach ($attrs as $key => $value) {
            if (Schema::hasColumn($table, $key)) {
                $filtered[$key] = $value;
            }
        }

        return static::create($filtered);
    }

    protected $casts = [
        'product_price' => 'float',
        'total_price' => 'float',
        'merchant_offer_price' => 'float',
        'delivery_lat' => 'float',
        'delivery_lng' => 'float',
        'delivery_fee' => 'float',
        'commission_amount' => 'float',
        'commission_applied' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        $fk = Schema::hasColumn($this->getTable(), 'merchant_id')
            ? 'merchant_id'
            : 'store_id';

        return $this->belongsTo(Merchant::class, $fk);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, Schema::hasColumn($this->getTable(), 'store_id') ? 'store_id' : 'merchant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MerchantProduct::class, 'product_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function invitedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_driver_id');
    }

    public function deliveryOffers(): HasMany
    {
        return $this->hasMany(ServiceDeliveryOffer::class);
    }
}
