<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public static function permissionKeys(): array
    {
        return [
            'dashboard' => 'الرئيسية / الإحصائيات',
            'users' => 'إدارة المستخدمين',
            'categories' => 'إدارة الأقسام',
            'products' => 'منتجات أصحاب النشاط',
            'commission' => 'العمولة والمستحقات',
            'payments' => 'طرق الدفع',
            'notifications' => 'الإشعارات',
            'admins' => 'إدارة المشرفين',
            'documents' => 'مستندات التسجيل',
            'plans' => 'الخطط',
            'merchants' => 'المتاجر والطلبات',
        ];
    }

    public function index()
    {
        $admins = User::where('is_admin', 1)->orderBy('name')->get();

        return view('admin.sections.admins.index', [
            'admins' => $admins,
            'permissionKeys' => self::permissionKeys(),
        ]);
    }

    public function create()
    {
        return view('admin.sections.admins.create', [
            'permissionKeys' => self::permissionKeys(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'title' => 'user',
            'is_admin' => 1,
            'admin_permissions' => $request->input('permissions', []),
        ]);

        return redirect()->route('admin.admins.index')->with('success', 'تم إضافة المشرف');
    }

    public function edit(int $id)
    {
        $admin = User::where('is_admin', 1)->findOrFail($id);

        return view('admin.sections.admins.edit', [
            'admin' => $admin,
            'permissionKeys' => self::permissionKeys(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $admin = User::where('is_admin', 1)->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id,
            'password' => 'nullable|min:8|confirmed',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_suspended' => 'nullable|boolean',
        ]);

        $admin->name = $data['name'];
        $admin->email = $data['email'];
        $admin->admin_permissions = $request->input('permissions', []);
        $admin->is_suspended = $request->boolean('is_suspended');

        if (!empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }

        $admin->save();

        return redirect()->route('admin.admins.index')->with('success', 'تم تحديث المشرف');
    }

    public function destroy(int $id)
    {
        if ((int) Auth::id() === $id) {
            return back()->with('error', 'لا يمكنك حذف حسابك');
        }

        $admin = User::where('is_admin', 1)->findOrFail($id);
        $admin->delete();

        return back()->with('success', 'تم حذف المشرف');
    }
}
