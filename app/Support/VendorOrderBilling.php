<?php

namespace App\Support;

use App\Models\Merchant;
use App\Models\Setting;
use App\Models\User;

class VendorOrderBilling
{
    /** أقسام تخضع للفوترة. «خدمات أخرى» مجانية دائماً ولا تُحتسب عليها مستحقات. */
    public const BILLABLE_SECTIONS = ['orders', 'bookings'];

    public static function commissionEnabled(): bool
    {
        return (Setting::getValue('commission_enabled', '1') ?? '1') !== '0';
    }

    public static function isBillableSection(?string $section): bool
    {
        return in_array(self::normalizeSection($section), self::BILLABLE_SECTIONS, true);
    }

    public static function commissionOptionEnabled(string $section = 'orders'): bool
    {
        if (!self::isBillableSection($section)) {
            return false;
        }

        $key = self::normalizeSection($section) === 'bookings'
            ? 'vendor_bookings_commission_enabled'
            : 'vendor_orders_commission_enabled';

        return self::isToggleOn($key, self::defaultToggle($section));
    }

    public static function fixedFeeOptionEnabled(string $section = 'orders'): bool
    {
        if (!self::isBillableSection($section)) {
            return false;
        }

        $key = self::normalizeSection($section) === 'bookings'
            ? 'vendor_bookings_fixed_fee_enabled'
            : 'vendor_orders_fixed_fee_enabled';

        return self::isToggleOn($key, self::defaultToggle($section));
    }

    public static function commissionPercent(string $section = 'orders'): float
    {
        if (self::normalizeSection($section) === 'bookings') {
            return (float) (Setting::getValue('vendor_bookings_commission_percent', '5') ?? 5);
        }

        $percent = Setting::getValue('vendor_orders_commission_percent');
        if ($percent === null || $percent === '') {
            $percent = Setting::getValue('merchant_commission_rate', '5');
        }

        return (float) $percent;
    }

    public static function fixedFeeAmount(string $section = 'orders'): float
    {
        $key = self::normalizeSection($section) === 'bookings'
            ? 'vendor_bookings_fixed_fee_amount'
            : 'vendor_orders_fixed_fee_amount';

        return (float) (Setting::getValue($key, '10') ?? 10);
    }

    /** الوضع المطبّق فعلياً على صاحب النشاط بعد مراعاة اختياره وما هو مفعّل في اللوحة. */
    public static function resolveMode(?User $owner, string $section = 'orders'): ?string
    {
        if (!self::isBillableSection($section)) {
            return null;
        }

        $commissionOn = self::commissionOptionEnabled($section);
        $fixedOn = self::fixedFeeOptionEnabled($section);

        if (!$commissionOn && !$fixedOn) {
            return null;
        }
        if ($fixedOn && !$commissionOn) {
            return 'fixed_fee';
        }
        if ($commissionOn && !$fixedOn) {
            return 'commission';
        }

        $saved = $owner && isset($owner->vendor_billing_mode)
            ? strtolower(trim((string) $owner->vendor_billing_mode))
            : '';

        return $saved === 'fixed_fee' ? 'fixed_fee' : 'commission';
    }

    public static function dueAmount(?User $owner, float $orderAmount, string $section = 'orders'): float
    {
        $section = self::normalizeSection($section);
        $mode = self::resolveMode($owner, $section);

        if ($mode === 'fixed_fee') {
            return round(self::fixedFeeAmount($section), 2);
        }
        if ($mode === 'commission') {
            return round($orderAmount * (self::commissionPercent($section) / 100), 2);
        }

        return 0.0;
    }

    public static function dueAmountForMerchant(?Merchant $merchant, float $orderAmount): float
    {
        if (!$merchant) {
            return 0.0;
        }

        return self::dueAmount(
            $merchant->owner,
            $orderAmount,
            $merchant->category?->billingSection() ?? 'orders'
        );
    }

    private static function normalizeSection(?string $section): string
    {
        $section = strtolower(trim((string) $section));

        return in_array($section, ['orders', 'bookings', 'services'], true) ? $section : 'orders';
    }

    private static function isToggleOn(string $key, string $default): bool
    {
        $raw = strtolower(trim((string) (Setting::getValue($key, $default) ?? $default)));

        return in_array($raw, ['1', 'true', 'yes', 'on', 'enabled'], true);
    }

    /** الحجوزات مجانية افتراضياً حتى تُفعّل من اللوحة. */
    private static function defaultToggle(?string $section): string
    {
        return self::normalizeSection($section) === 'bookings' ? '0' : '1';
    }

    /** @deprecated استخدم commissionOptionEnabled('orders') */
    public static function ordersCommissionEnabled(): bool
    {
        return self::commissionOptionEnabled('orders');
    }

    /** @deprecated استخدم fixedFeeOptionEnabled('orders') */
    public static function ordersFixedFeeEnabled(): bool
    {
        return self::fixedFeeOptionEnabled('orders');
    }
}
