<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminPasswordController extends Controller
{
    public function edit()
    {
        return view('admin.sections.account.password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'أدخل كلمة المرور الحالية',
            'password.required' => 'أدخل كلمة المرور الجديدة',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
        ]);

        $user = Auth::user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة'])
                ->withInput();
        }

        if (Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['password' => 'كلمة المرور الجديدة مطابقة للحالية'])
                ->withInput();
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        return redirect()
            ->route('admin.password.edit')
            ->with('success', 'تم تغيير كلمة المرور بنجاح');
    }
}
