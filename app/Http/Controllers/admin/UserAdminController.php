<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('type', 'all');
        $segment = strtolower(trim((string) $request->get('segment', 'current')));
        if (!in_array($segment, ['new', 'current'], true)) {
            $segment = 'current';
        }
        $search = trim((string) $request->get('search', ''));

        $query = User::query()->where(function ($q) {
            $q->where('is_admin', 0)->orWhereNull('is_admin');
        })->with(['ownedMerchant' => function ($q) {
            $q->withCount('products');
        }]);

        match ($filter) {
            'drivers' => $query->where('title', 'driver'),
            'merchants' => $query->whereIn('title', ['merchant', 'vendor']),
            'users', 'customers' => $query->where('title', 'user'),
            default => null,
        };

        if ($filter === 'drivers') {
            if ($segment === 'new') {
                $query->where(function ($q) {
                    $q->where('is_approved', 0)->orWhereNull('is_approved');
                });
            } else {
                $query->where('is_approved', 1);
            }
        }

        if ($search !== '') {
            $query->adminSearch($search);
        }

        $users = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $stats = [
            'all' => User::where(function ($q) {
                $q->where('is_admin', 0)->orWhereNull('is_admin');
            })->count(),
            'drivers' => User::where('title', 'driver')->count(),
            'drivers_current' => User::where('title', 'driver')->where('is_approved', 1)->count(),
            'drivers_new' => User::where('title', 'driver')->where(function ($q) {
                $q->where('is_approved', 0)->orWhereNull('is_approved');
            })->count(),
            'merchants' => User::whereIn('title', ['merchant', 'vendor'])->count(),
            'users' => User::where('title', 'user')->count(),
        ];

        return view('admin.sections.users.users', compact('users', 'filter', 'stats', 'segment', 'search'));
    }

    public function show(int $id)
    {
        $user = User::with(['ownedMerchant.category', 'subscription.plan'])->findOrFail($id);

        if ($user->is_admin) {
            abort(404);
        }

        return view('admin.sections.users.show', compact('user'));
    }

    public function toggleSuspend(int $id)
    {
        $user = User::where('is_admin', 0)->findOrFail($id);
        $user->update(['is_suspended' => !$user->is_suspended]);

        $msg = $user->is_suspended ? 'تم إيقاف الحساب مؤقتاً' : 'تم تفعيل الحساب';

        return back()->with('success', $msg);
    }

    public function approve(int $id)
    {
        $user = User::where('is_admin', 0)->findOrFail($id);
        $user->update(['is_approved' => 1]);

        return back()->with('success', 'تم اعتماد الحساب');
    }

    public function reject(int $id)
    {
        $user = User::where('is_admin', 0)->findOrFail($id);
        $user->update(['is_approved' => 0]);

        return back()->with('success', 'تم رفض اعتماد الحساب');
    }

    public function destroy(int $id)
    {
        $user = User::where('is_admin', 0)->findOrFail($id);
        $user->delete();

        return redirect()->route('admin.user.index')->with('success', 'تم حذف المستخدم');
    }
}
