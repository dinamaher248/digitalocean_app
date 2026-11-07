<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceDeliveryOffer;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Support\Facades\DB;

class ServiceOrderAdminController extends Controller
{
    public function index()
    {
        $orders = ServiceOrder::with(['merchant', 'user', 'deliveryOffers.driver'])
            ->orderByDesc('id')
            ->paginate(30);

        return view('admin.sections.service_orders.index', compact('orders'));
    }

    public function accept(int $id)
    {
        $order = ServiceOrder::with(['user', 'merchant'])->findOrFail($id);

        if (!in_array($order->status, ['new', 'awaiting_payment_review'], true)) {
            return back()->with('error', 'لا يمكن قبول الطلب في حالته الحالية');
        }

        $order->update(['status' => 'preparing']);

        $customer = $order->user;
        if ($customer) {
            app(FcmService::class)->sendToUser(
                $customer,
                'تم قبول طلبك وجاري التجهيز',
                'جاري تجهيز طلبك من ' . ($order->merchant->name ?? ''),
                'service_order_accepted',
                (string) $order->id,
            );
        }

        $order->update(['status' => 'awaiting_driver']);
        $this->notifyDrivers($order);

        return back()->with('success', 'تم قبول الطلب وإرساله للسائقين');
    }

    public function reject(int $id)
    {
        $order = ServiceOrder::with('user')->findOrFail($id);

        if (!in_array($order->status, ['new', 'awaiting_payment_review'], true)) {
            return back()->with('error', 'لا يمكن رفض الطلب في حالته الحالية');
        }

        $order->update(['status' => 'merchant_rejected']);

        if ($order->user) {
            app(FcmService::class)->sendToUser(
                $order->user,
                'تم رفض الطلب',
                'عذرًا، المتجر رفض طلبك',
                'service_order_rejected',
                (string) $order->id,
            );
        }

        return back()->with('success', 'تم رفض الطلب');
    }

    public function acceptDriverOffer(int $orderId, int $offerId)
    {
        $order = ServiceOrder::findOrFail($orderId);
        $offer = ServiceDeliveryOffer::where('service_order_id', $order->id)
            ->where('id', $offerId)
            ->where('status', 'pending')
            ->firstOrFail();

        DB::transaction(function () use ($order, $offer) {
            $offer->update(['status' => 'accepted']);
            $order->deliveryOffers()->where('id', '!=', $offer->id)->update(['status' => 'rejected']);
            $order->update([
                'status' => 'driver_assigned',
                'driver_id' => $offer->driver_id,
                'accepted_delivery_offer_id' => $offer->id,
                'delivery_fee' => $offer->amount,
            ]);
        });

        return back()->with('success', 'تم قبول عرض السائق');
    }

    private function notifyDrivers(ServiceOrder $order): void
    {
        $drivers = User::query()
            ->where('title', 'driver')
            ->where('is_online', true)
            ->whereIn('vehicle_type', ['motorcycle', 'toktok'])
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->limit(40)
            ->get();

        $fcm = app(FcmService::class);
        foreach ($drivers as $driver) {
            $fcm->sendToUser(
                $driver,
                'طلب توصيل جديد',
                'استلام من: ' . ($order->merchant->name ?? ''),
                'new_service_delivery',
                (string) $order->id,
            );
        }
    }
}
