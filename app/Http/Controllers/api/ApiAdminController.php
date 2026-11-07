<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ApiAdminController extends Controller
{
    private function adminOrAbort()
    {
        $user = Auth::guard('api')->user();
        if (!$user || !(int) $user->is_admin) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح — هذه الواجهة للمشرفين فقط',
            ], 403);
        }

        return null;
    }

    private function mapUser(User $user): array
    {
        $suspended = (bool) $user->is_suspended;
        $documents = [];

        if (!empty($user->nid_image)) {
            $documents[] = [
                'label' => 'صورة البطاقة / الهوية',
                'url' => $user->nid_image,
                'image' => $user->nid_image,
            ];
        }
        if (!empty($user->license_image)) {
            $documents[] = [
                'label' => 'رخصة القيادة',
                'url' => $user->license_image,
                'image' => $user->license_image,
            ];
        }
        if (!empty($user->image)) {
            $documents[] = [
                'label' => 'الصورة الشخصية',
                'url' => $user->image,
                'image' => $user->image,
            ];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'title' => $user->title,
            'role' => $user->title,
            'type' => $user->title,
            'coins' => $user->coins ?? 0,
            'is_active' => !$suspended,
            'active' => !$suspended,
            'is_suspended' => $suspended,
            'is_approved' => (int) ($user->is_approved ?? 0),
            'status_label' => $suspended ? 'موقوف' : 'مفعل',
            'vehicle_type' => $user->vehicle_type,
            'governorate' => $user->governorate,
            'area' => $user->area,
            'is_online' => (bool) ($user->is_online ?? false),
            'documents' => $documents,
            'created_at' => optional($user->created_at)?->toIso8601String(),
        ];
    }

    public function stats()
    {
        if ($deny = $this->adminOrAbort()) {
            return $deny;
        }

        $revenue = 0.0;
        if (Schema::hasTable('service_orders')) {
            $completed = ServiceOrder::where('status', 'completed');
            if (Schema::hasColumn('service_orders', 'total_price')) {
                $revenue = (float) $completed->sum('total_price');
            } else {
                $product = Schema::hasColumn('service_orders', 'product_total')
                    ? (float) ServiceOrder::where('status', 'completed')->sum('product_total')
                    : 0.0;
                $delivery = Schema::hasColumn('service_orders', 'delivery_fee')
                    ? (float) ServiceOrder::where('status', 'completed')->sum('delivery_fee')
                    : 0.0;
                $revenue = $product + $delivery;
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'clients_count' => User::where('is_admin', 0)->where('title', 'user')->count(),
                'drivers_count' => User::where('title', 'driver')->count(),
                'vendors_count' => User::where('title', 'merchant')->count(),
                'total_revenue' => $revenue,
                'active_orders' => ServiceOrder::whereIn('status', [
                    'new',
                    'pending',
                    'awaiting_payment_review',
                    'awaiting_merchant_offer',
                    'accepted',
                    'merchant_accepted',
                    'in_progress',
                ])->count(),
                'customers' => User::where('is_admin', 0)->where('title', 'user')->count(),
                'drivers' => User::where('title', 'driver')->count(),
                'vendors' => User::where('title', 'merchant')->count(),
                'clients' => User::where('is_admin', 0)->where('title', 'user')->count(),
            ],
        ]);
    }

    public function users(Request $request)
    {
        if ($deny = $this->adminOrAbort()) {
            return $deny;
        }

        $role = strtolower(trim((string) (
            $request->get('role')
            ?? $request->get('type')
            ?? $request->get('title')
            ?? 'user'
        )));

        $query = User::query()->where('is_admin', 0);

        match ($role) {
            'driver', 'drivers' => $query->where('title', 'driver'),
            'vendor', 'vendors', 'merchant', 'merchants' => $query->where('title', 'merchant'),
            'client', 'clients', 'customer', 'users', 'customer', 'customers' => $query->where('title', 'user'),
            'all' => null,
            default => $query->where('title', 'user'),
        };

        $users = $query->orderByDesc('id')->limit(200)->get();

        return response()->json([
            'status' => true,
            'data' => $users->map(fn (User $u) => $this->mapUser($u))->values(),
            'users' => $users->map(fn (User $u) => $this->mapUser($u))->values(),
        ]);
    }

    public function toggleUser(int $id)
    {
        if ($deny = $this->adminOrAbort()) {
            return $deny;
        }

        $user = User::where('is_admin', 0)->findOrFail($id);
        $user->update(['is_suspended' => !$user->is_suspended]);
        $user->refresh();

        return response()->json([
            'status' => true,
            'message' => $user->is_suspended ? 'تم إيقاف الحساب مؤقتاً' : 'تم تفعيل الحساب',
            'data' => $this->mapUser($user),
        ]);
    }

    public function reviewUser(int $id)
    {
        if ($deny = $this->adminOrAbort()) {
            return $deny;
        }

        $user = User::with(['ownedMerchant.category'])->where('is_admin', 0)->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $this->mapUser($user),
        ]);
    }
}
