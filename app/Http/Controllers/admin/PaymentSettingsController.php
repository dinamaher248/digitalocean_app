<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PaymentSettingsController extends Controller
{
    public function edit()
    {
        $settings = $this->loadSettings();

        return view('admin.sections.settings.payment_methods', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'wallet_number' => 'nullable|string|max:30',
            'instapay_number' => 'nullable|string|max:100',
            'wallet_enabled' => 'nullable|boolean',
            'instapay_enabled' => 'nullable|boolean',
        ]);

        Setting::setValue('wallet_number', $data['wallet_number'] ?? '');
        Setting::setValue('instapay_number', $data['instapay_number'] ?? '');
        Setting::setValue('wallet_enabled', $request->boolean('wallet_enabled') ? '1' : '0');
        Setting::setValue('instapay_enabled', $request->boolean('instapay_enabled') ? '1' : '0');

        return back()->with('success', 'تم حفظ إعدادات الدفع');
    }

    private function loadSettings(): array
    {
        if (!Schema::hasTable('settings')) {
            return [
                'wallet_number' => '',
                'instapay_number' => '',
                'wallet_enabled' => true,
                'instapay_enabled' => true,
            ];
        }

        return [
            'wallet_number' => Setting::getValue('wallet_number', '') ?? '',
            'instapay_number' => Setting::getValue('instapay_number', '') ?? '',
            'wallet_enabled' => Setting::getValue('wallet_enabled', '1') === '1',
            'instapay_enabled' => Setting::getValue('instapay_enabled', '1') === '1',
        ];
    }
}
