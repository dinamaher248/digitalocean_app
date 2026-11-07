<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SettlementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CommissionSettingsController extends Controller
{
    public function edit()
    {
        $settings = $this->loadSettings();
        $settlements = Schema::hasTable('settlement_requests')
            ? SettlementRequest::with('user')->orderByDesc('created_at')->limit(30)->get()
            : collect();

        return view('admin.sections.settings.commission', compact('settings', 'settlements'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'commission_enabled' => 'nullable|boolean',
            'driver_commission_rate' => 'required|numeric|min:0|max:100',
            'merchant_commission_rate' => 'required|numeric|min:0|max:100',
            'dues_debt_limit' => 'required|numeric|min:0',
            'vendor_orders_commission_percent' => 'nullable|numeric|min:0|max:100',
            'vendor_orders_fixed_fee_amount' => 'nullable|numeric|min:0',
            'vendor_bookings_commission_percent' => 'nullable|numeric|min:0|max:100',
            'vendor_bookings_fixed_fee_amount' => 'nullable|numeric|min:0',
            'driver_fixed_fee_amount' => 'nullable|numeric|min:0',
        ]);

        $driverCommissionOn = $request->boolean('driver_commission_enabled');
        $driverFixedOn = $request->boolean('driver_fixed_fee_enabled');
        Setting::setValue('driver_commission_enabled', $driverCommissionOn ? '1' : '0');
        Setting::setValue('driver_fixed_fee_enabled', $driverFixedOn ? '1' : '0');
        Setting::setValue('driver_fixed_fee_amount', (string) ($data['driver_fixed_fee_amount'] ?? 10));
        Setting::setValue('commission_enabled', $driverCommissionOn ? '1' : '0');
        Setting::setValue('driver_commission_rate', (string) $data['driver_commission_rate']);
        Setting::setValue('merchant_commission_rate', (string) $data['merchant_commission_rate']);
        Setting::setValue('dues_debt_limit', (string) $data['dues_debt_limit']);
        Setting::setValue('driver_commission_percent', (string) $data['driver_commission_rate']);
        Setting::setValue('vendor_commission_percent', (string) $data['merchant_commission_rate']);
        Setting::setValue('debt_limit', (string) $data['dues_debt_limit']);

        Setting::setValue(
            'vendor_orders_commission_enabled',
            $request->boolean('vendor_orders_commission_enabled') ? '1' : '0'
        );
        Setting::setValue(
            'vendor_orders_fixed_fee_enabled',
            $request->boolean('vendor_orders_fixed_fee_enabled') ? '1' : '0'
        );
        Setting::setValue(
            'vendor_orders_commission_percent',
            (string) ($data['vendor_orders_commission_percent'] ?? 5)
        );
        Setting::setValue(
            'vendor_orders_fixed_fee_amount',
            (string) ($data['vendor_orders_fixed_fee_amount'] ?? 10)
        );

        // الحجوزات: الإعداد متاح لكن مجاني حتى تُفعّل الخيارات من هنا.
        Setting::setValue(
            'vendor_bookings_commission_enabled',
            $request->boolean('vendor_bookings_commission_enabled') ? '1' : '0'
        );
        Setting::setValue(
            'vendor_bookings_fixed_fee_enabled',
            $request->boolean('vendor_bookings_fixed_fee_enabled') ? '1' : '0'
        );
        Setting::setValue(
            'vendor_bookings_commission_percent',
            (string) ($data['vendor_bookings_commission_percent'] ?? 5)
        );
        Setting::setValue(
            'vendor_bookings_fixed_fee_amount',
            (string) ($data['vendor_bookings_fixed_fee_amount'] ?? 10)
        );

        return back()->with('success', 'تم حفظ إعدادات العمولة والمستحقات');
    }

    private function loadSettings(): array
    {
        if (!Schema::hasTable('settings')) {
            return [
                'commission_enabled' => true,
                'driver_commission_enabled' => true,
                'driver_fixed_fee_enabled' => false,
                'driver_fixed_fee_amount' => 10,
                'driver_commission_rate' => 10,
                'merchant_commission_rate' => 5,
                'dues_debt_limit' => 500,
                'vendor_orders_commission_enabled' => true,
                'vendor_orders_fixed_fee_enabled' => true,
                'vendor_orders_commission_percent' => 5,
                'vendor_orders_fixed_fee_amount' => 10,
                'vendor_bookings_commission_enabled' => false,
                'vendor_bookings_fixed_fee_enabled' => false,
                'vendor_bookings_commission_percent' => 5,
                'vendor_bookings_fixed_fee_amount' => 10,
            ];
        }

        return [
            'commission_enabled' => Setting::getValue('commission_enabled', '1') === '1',
            'driver_commission_enabled' => \App\Support\DriverBilling::commissionOptionEnabled(),
            'driver_fixed_fee_enabled' => \App\Support\DriverBilling::fixedFeeOptionEnabled(),
            'driver_fixed_fee_amount' => \App\Support\DriverBilling::fixedFeeAmount(),
            'driver_commission_rate' => (float) (Setting::getValue('driver_commission_rate', '10') ?? 10),
            'merchant_commission_rate' => (float) (Setting::getValue('merchant_commission_rate', '5') ?? 5),
            'dues_debt_limit' => (float) (Setting::getValue('dues_debt_limit', '500') ?? 500),
            'vendor_orders_commission_enabled' => Setting::getValue('vendor_orders_commission_enabled', '1') !== '0',
            'vendor_orders_fixed_fee_enabled' => Setting::getValue('vendor_orders_fixed_fee_enabled', '1') !== '0',
            'vendor_orders_commission_percent' => (float) (
                Setting::getValue('vendor_orders_commission_percent', '5') ?? 5
            ),
            'vendor_orders_fixed_fee_amount' => (float) (Setting::getValue('vendor_orders_fixed_fee_amount', '10') ?? 10),
            'vendor_bookings_commission_enabled' => Setting::getValue('vendor_bookings_commission_enabled', '0') === '1',
            'vendor_bookings_fixed_fee_enabled' => Setting::getValue('vendor_bookings_fixed_fee_enabled', '0') === '1',
            'vendor_bookings_commission_percent' => (float) (
                Setting::getValue('vendor_bookings_commission_percent', '5') ?? 5
            ),
            'vendor_bookings_fixed_fee_amount' => (float) (
                Setting::getValue('vendor_bookings_fixed_fee_amount', '10') ?? 10
            ),
        ];
    }
}
