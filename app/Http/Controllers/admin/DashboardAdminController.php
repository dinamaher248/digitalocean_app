<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\ServiceCategory;
use App\Models\ServiceOrder;
use App\Models\SettlementRequest;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class DashboardAdminController extends Controller
{
    public function index()
    {
        $revenueColumn = Schema::hasColumn('service_orders', 'total_price')
            ? 'total_price'
            : (Schema::hasColumn('service_orders', 'product_total') ? 'product_total' : null);

        $stats = [
            'users_total' => User::where('is_admin', 0)->count(),
            'customers' => User::where('title', 'user')->count(),
            'drivers' => User::where('title', 'driver')->count(),
            'merchants' => User::where('title', 'merchant')->count(),
            'active_merchants' => Merchant::where('is_active', true)->count(),
            'products' => MerchantProduct::count(),
            'categories' => ServiceCategory::count(),
            'service_orders' => ServiceOrder::count(),
            'pending_orders' => ServiceOrder::whereIn('status', ['new', 'awaiting_payment_review', 'awaiting_merchant_offer'])->count(),
            'completed_orders' => ServiceOrder::where('status', 'completed')->count(),
            'orders_revenue' => $revenueColumn
                ? (float) ServiceOrder::where('status', 'completed')->sum($revenueColumn)
                : 0,
            'commission_total' => Schema::hasColumn('service_orders', 'commission_amount')
                ? (float) ServiceOrder::where('status', 'completed')->sum('commission_amount')
                : 0,
            'dues_outstanding' => Schema::hasColumn('users', 'dues_balance')
                ? (float) User::whereIn('title', ['driver', 'merchant'])->sum('dues_balance')
                : (Schema::hasColumn('users', 'due_amount')
                    ? (float) User::whereIn('title', ['driver', 'merchant', 'vendor'])->sum('due_amount')
                    : 0),
            'pending_settlements' => class_exists(SettlementRequest::class)
                ? SettlementRequest::where('status', 'pending')->count()
                : 0,
            'pending_subscriptions' => class_exists(Subscription::class)
                ? Subscription::where('status', 'pending')->count()
                : 0,
            'suspended_users' => Schema::hasColumn('users', 'is_suspended')
                ? User::where('is_suspended', true)->count()
                : 0,
        ];

        $recentOrders = ServiceOrder::with('merchant')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $recentUsers = User::where('is_admin', 0)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'recentUsers'));
    }
}
