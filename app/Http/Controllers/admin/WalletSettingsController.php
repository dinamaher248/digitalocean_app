<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WalletSettingsController extends Controller
{
    public function edit()
    {
        $walletNumber = '';

        if (Schema::hasTable('settings')) {
            $walletNumber = Setting::getValue('wallet_number', '') ?? '';
        }

        return view('admin.sections.settings.wallet_settings', [
            'walletNumber' => $walletNumber,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
        ], [
            'phone.required' => 'رقم المحفظة مطلوب',
        ]);

        Setting::setValue('wallet_number', $data['phone']);

        return redirect()
            ->route('admin.wallet_settings.edit')
            ->with('success', 'تم حفظ رقم المحفظة بنجاح');
    }
}
