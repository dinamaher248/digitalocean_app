<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\WelcomeScreenService;
use Illuminate\Http\Request;

class WelcomeSettingsController extends Controller
{
    public function edit()
    {
        return view('admin.sections.settings.welcome_settings', WelcomeScreenService::adminFormData());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'features_text' => 'required|string|max:5000',
            'button_text' => 'required|string|max:100',
        ]);

        $data['enabled'] = $request->boolean('enabled');

        WelcomeScreenService::updateFromRequest($data);

        return redirect()
            ->route('admin.welcome_settings.edit')
            ->with('success', 'تم حفظ إعدادات شاشة الترحيب بنجاح');
    }
}
