<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

class DriverBilling
{
    public static function commissionOptionEnabled(): bool
    {
        $value = Setting::getValue('driver_commission_enabled');
        if ($value === null || $value === '') {
            return (Setting::getValue('commission_enabled', '1') ?? '1') !== '0';
        }

        return $value !== '0';
    }

    public static function fixedFeeOptionEnabled(): bool
    {
        return (Setting::getValue('driver_fixed_fee_enabled', '0') ?? '0') === '1';
    }

    public static function commissionPercent(?User $driver = null): float
    {
        $fromDriver = $driver !== null ? (float) ($driver->commission_rate ?? 0) : 0;
        if ($fromDriver > 0) {
            return $fromDriver;
        }

        return (float) (Setting::getValue('driver_commission_rate', '10') ?? 10);
    }

    public static function fixedFeeAmount(): float
    {
        return (float) (Setting::getValue('driver_fixed_fee_amount', '10') ?? 10);
    }

    public static function resolveMode(?User $driver): ?string
    {
        $commissionOn = self::commissionOptionEnabled();
        $fixedOn = self::fixedFeeOptionEnabled();

        if (!$commissionOn && !$fixedOn) {
            return null;
        }
        if ($fixedOn && !$commissionOn) {
            return 'fixed_fee';
        }
        if ($commissionOn && !$fixedOn) {
            return 'commission';
        }

        $saved = $driver && isset($driver->vendor_billing_mode)
            ? strtolower(trim((string) $driver->vendor_billing_mode))
            : '';

        return $saved === 'fixed_fee' ? 'fixed_fee' : 'commission';
    }

    public static function dueAmount(?User $driver, float $orderAmount): float
    {
        $mode = self::resolveMode($driver);
        if ($mode === 'fixed_fee') {
            return round(self::fixedFeeAmount(), 2);
        }
        if ($mode === 'commission') {
            return round($orderAmount * (self::commissionPercent($driver) / 100), 2);
        }

        return 0.0;
    }
}
