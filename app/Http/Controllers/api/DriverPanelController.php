<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\ApiBiddingController;
use App\Http\Controllers\api\LocalServiceController;
use App\Http\Controllers\api\TripRequestController;
use App\Models\BiddingTrip;
use App\Models\RideBook;
use App\Models\ServiceDeliveryOffer;
use App\Models\ServiceOrder;
use App\Models\SettlementRequest;
use App\Models\Setting;
use App\Models\TripOffer;
use App\Models\TripRequest;
use App\Models\User;
use App\Services\FcmService;
use App\Services\DriverProximityService;
use App\Services\MerchantOrderNotificationService;
use App\Support\DriverBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DriverPanelController extends Controller
{
    public function availableOrders(Request $request)
    {
        $driver = $this->requireDriver();

        if (!(bool) $driver->is_online) {
            return response()->json(['status_code' => 200, 'data' => []]);
        }

        $vehicle = $this->normalizeVehicle($driver->vehicle_type);
        $matchKeys = \App\Models\VehicleType::matchingKeys($driver->vehicle_type);
        $proximity = app(DriverProximityService::class);
        $orders = [];

        // بدون وسيلة نقل محددة لا تُعرض أي طلبات.
        $tripRequests = $matchKeys === []
            ? collect()
            : TripRequest::query()
                ->with('user')
                ->where('status', 'awaiting_offers')
                ->whereIn('vehicle_type', $matchKeys)
                ->orderByDesc('created_at')
                ->limit(80)
                ->get()
                ->filter(function (TripRequest $trip) use ($driver, $proximity) {
                    return $proximity->driverSeesPickup(
                        $driver,
                        $trip->pick_lat !== null ? (float) $trip->pick_lat : null,
                        $trip->pick_lng !== null ? (float) $trip->pick_lng : null,
                        $trip->vehicle_type
                    );
                });

        foreach ($tripRequests as $trip) {
            $myOffer = TripOffer::where('trip_request_id', $trip->id)
                ->where('driver_id', $driver->id)
                ->first();

            $orders[] = [
                'id' => $trip->id,
                'type' => 'trip_request',
                'title' => $trip->service_type === 'quick_trip' ? 'مشوار سريع' : 'نقل بضائع',
                'pick_loc' => $trip->pick_loc,
                'target_loc' => $trip->target_loc,
                'pick_lat' => $trip->pick_lat !== null ? (float) $trip->pick_lat : null,
                'pick_lng' => $trip->pick_lng !== null ? (float) $trip->pick_lng : null,
                'target_lat' => $trip->target_lat !== null ? (float) $trip->target_lat : null,
                'target_lng' => $trip->target_lng !== null ? (float) $trip->target_lng : null,
                'distance_label' => $trip->distance_km ? $trip->distance_km . ' كم' : null,
                'description' => $trip->description,
                'status' => $trip->status,
                'can_offer' => $myOffer === null,
                'my_offer_amount' => $myOffer?->amount,
                'offer_status' => $myOffer?->status,
                'show_contact' => false,
                'customer_name' => $trip->user?->name,
                'customer_rating' => round((float) ($trip->user?->avg_rating ?? 0), 1),
                'customer_ratings_count' => (int) ($trip->user?->ratings_count ?? 0),
                'created_at' => optional($trip->created_at)->format('Y-m-d H:i'),
            ];
        }

        $assignedTrips = TripRequest::with(['user', 'acceptedOffer'])
            ->whereIn('status', ['in_progress', 'handed_to_driver'])
            ->whereHas('acceptedOffer', fn ($q) => $q->where('driver_id', $driver->id))
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        foreach ($assignedTrips as $trip) {
            $orders[] = [
                'id' => $trip->id,
                'type' => 'trip_request',
                'title' => 'طلب مقبول',
                'pick_loc' => $trip->pick_loc,
                'target_loc' => $trip->target_loc,
                'distance_label' => $trip->distance_km ? $trip->distance_km . ' كم' : null,
                'description' => $trip->description,
                'status' => $trip->status,
                'can_offer' => false,
                'my_offer_amount' => $trip->acceptedOffer?->amount,
                'offer_status' => 'accepted',
                'show_contact' => true,
                'customer_name' => $trip->user?->name,
                'customer_phone' => $trip->user?->phone,
                'customer_id' => $trip->user_id,
                'customer_rating' => round((float) ($trip->user?->avg_rating ?? 0), 1),
                'customer_ratings_count' => (int) ($trip->user?->ratings_count ?? 0),
                'ride_book_id' => $trip->ride_book_id,
                'created_at' => optional($trip->created_at)->format('Y-m-d H:i'),
            ];
        }

        $bookings = RideBook::with('user')
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['pending', 'accepted', 'in_progress'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        foreach ($bookings as $booking) {
            $orders[] = [
                'id' => $booking->id,
                'type' => 'ride_book',
                'title' => 'حجز مباشر',
                'pick_loc' => $booking->pick_loc,
                'target_loc' => $booking->target_loc,
                'distance_label' => null,
                'description' => $booking->msg,
                'status' => $booking->status,
                'can_offer' => false,
                'show_contact' => in_array($booking->status, ['accepted', 'in_progress'], true),
                'customer_name' => $booking->user?->name,
                'customer_phone' => $booking->user?->phone,
                'customer_id' => $booking->user_id,
                'customer_rating' => round((float) ($booking->user?->avg_rating ?? 0), 1),
                'customer_ratings_count' => (int) ($booking->user?->ratings_count ?? 0),
                'ride_book_id' => $booking->id,
                'amount' => $booking->amount,
                'created_at' => optional($booking->created_at)->format('Y-m-d H:i'),
            ];
        }

        if ($vehicle === 'motorcycle') {
            $serviceOrders = ServiceOrder::with(['merchant', 'user'])
                ->where('status', 'awaiting_driver')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            foreach ($serviceOrders as $order) {
                $myOffer = ServiceDeliveryOffer::where('service_order_id', $order->id)
                    ->where('driver_id', $driver->id)
                    ->first();

                $orders[] = [
                    'id' => $order->id,
                    'type' => 'service_order',
                    'title' => 'توصيل خدمة',
                    'pick_loc' => $order->merchant?->name ?? 'المتجر',
                    'target_loc' => $order->delivery_address,
                    'distance_label' => null,
                    'description' => $order->product_name,
                    'status' => $order->status,
                    'can_offer' => $myOffer === null,
                    'my_offer_amount' => $myOffer?->amount,
                    'offer_status' => $myOffer?->status,
                    'show_contact' => false,
                    'customer_name' => $order->user?->name,
                    'customer_rating' => round((float) ($order->user?->avg_rating ?? 0), 1),
                    'customer_ratings_count' => (int) ($order->user?->ratings_count ?? 0),
                    'payment_method_label' => $order->payment_method,
                    'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
                ];
            }

            $assignedServices = ServiceOrder::with(['user', 'merchant'])
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['driver_assigned', 'in_delivery', 'handed_to_driver'])
                ->limit(20)
                ->get();

            foreach ($assignedServices as $order) {
                $orders[] = [
                    'id' => $order->id,
                    'type' => 'service_order',
                    'title' => 'توصيل خدمة — مقبول',
                    'pick_loc' => $order->merchant?->name,
                    'target_loc' => $order->delivery_address,
                    'distance_label' => null,
                    'description' => $order->product_name,
                    'status' => $order->status,
                    'can_offer' => false,
                    'show_contact' => true,
                    'customer_name' => $order->user?->name,
                    'customer_phone' => $order->user?->phone ?? $order->customer_contact_phone,
                    'customer_id' => $order->user_id,
                    'customer_rating' => round((float) ($order->user?->avg_rating ?? 0), 1),
                    'customer_ratings_count' => (int) ($order->user?->ratings_count ?? 0),
                    'delivery_fee' => $order->delivery_fee,
                    'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
                ];
            }

            $invitedServices = ServiceOrder::with(['merchant', 'user'])
                ->where('status', 'driver_invited')
                ->where('invited_driver_id', $driver->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            foreach ($invitedServices as $order) {
                $orders[] = [
                    'id' => $order->id,
                    'type' => 'service_order',
                    'title' => 'دعوة توصيل',
                    'pick_loc' => $order->merchant?->name,
                    'target_loc' => $order->delivery_address,
                    'distance_label' => null,
                    'description' => $order->order_type === 'custom'
                        ? 'طلب مخصص — ' . ($order->custom_request_text ?? $order->product_name)
                        : $order->product_name,
                    'status' => $order->status,
                    'can_offer' => false,
                    'can_accept_invitation' => true,
                    'order_total' => $order->total_price,
                    'show_contact' => false,
                    'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
                ];
            }
        }

        usort($orders, fn ($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        return response()->json(['status_code' => 200, 'data' => array_values($orders)]);
    }

    public function myCompletedOrders()
    {
        $driver = $this->requireDriver();
        $orders = [];

        $rideBooks = RideBook::with('user')
            ->where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        foreach ($rideBooks as $b) {
            $orders[] = $this->formatCompleted('ride_book', $b->id, $b->pick_loc, $b->target_loc, $b->msg, $b->amount, $b->updated_at);
        }

        $trips = TripRequest::with('acceptedOffer')
            ->where('status', 'completed')
            ->whereHas('acceptedOffer', fn ($q) => $q->where('driver_id', $driver->id))
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        foreach ($trips as $t) {
            $orders[] = $this->formatCompleted(
                'trip_request',
                $t->id,
                $t->pick_loc,
                $t->target_loc,
                $t->description,
                $t->acceptedOffer?->amount,
                $t->updated_at,
            );
        }

        $services = ServiceOrder::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        foreach ($services as $s) {
            $orders[] = $this->formatCompleted(
                'service_order',
                $s->id,
                $s->merchant?->name,
                $s->delivery_address,
                $s->product_name,
                $s->delivery_fee,
                $s->updated_at,
            );
        }

        usort($orders, fn ($a, $b) => strcmp($b['completed_at'] ?? '', $a['completed_at'] ?? ''));

        return response()->json(['status_code' => 200, 'data' => array_values($orders)]);
    }

    public function acceptServiceInvitation(int $id)
    {
        $driver = $this->requireDriver();

        $order = ServiceOrder::with(['merchant', 'user'])
            ->where('id', $id)
            ->where('status', 'driver_invited')
            ->where('invited_driver_id', $driver->id)
            ->firstOrFail();

        $booking = null;
        if (Schema::hasTable('ride_book') && $order->user_id) {
            $bookingPayload = [
                'driver_id' => $driver->id,
                'user_id' => $order->user_id,
                'amount' => $order->delivery_fee ?? $order->total_price,
                'msg' => $order->product_name,
                'pick_loc' => $order->merchant?->name,
                'target_loc' => $order->delivery_address,
                'status' => 'in_progress',
            ];
            if (Schema::hasColumn('ride_book', 'service_order_id')) {
                $bookingPayload['service_order_id'] = $order->id;
            }
            $booking = RideBook::create($bookingPayload);
        }

        $orderUpdate = [
            'status' => 'driver_assigned',
            'driver_id' => $driver->id,
        ];
        if ($booking && Schema::hasColumn('service_orders', 'ride_book_id')) {
            $orderUpdate['ride_book_id'] = $booking->id;
        }
        $order->update($orderUpdate);

        $order = $order->fresh(['merchant', 'user']);
        $customer = $order->user;
        if ($customer) {
            app(FcmService::class)->sendToUser(
                $customer,
                'تم تعيين سائق لطلبك',
                'سائق في الطريق لتوصيل طلبك من ' . ($order->merchant?->name ?? ''),
                'service_order_status',
                (string) $order->id,
            );
        }

        app(MerchantOrderNotificationService::class)->notifyOrderStatus(
            $order,
            'تحديث حالة الطلب',
            'قبل السائق طلب التوصيل #' . $order->id,
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'تم قبول طلب التوصيل',
        ]);
    }

    public function goToPickup(Request $request, string $type, int $id)
    {
        $request->merge(['action' => 'going_to_pickup']);
        return $this->updateProgress($request, $type, $id);
    }

    public function markPickedUp(Request $request, string $type, int $id)
    {
        $request->merge(['action' => 'picked_up']);
        return $this->updateProgress($request, $type, $id);
    }

    public function updateProgress(Request $request, string $type, int $id)
    {
        $driver = $this->requireDriver();
        $type = strtolower(trim($type));
        $type = match ($type) {
            'bidding', 'bidding_trips', 'trip' => 'bidding_trip',
            'service', 'local_service', 'order', 'catalog' => 'service_order',
            default => $type,
        };
        $action = strtolower(trim((string) (
            $request->input('action')
            ?: $request->query('action', '')
        )));

        if ($type === 'bidding_trip') {
            $query = BiddingTrip::with('user');
            $driverCol = BiddingTrip::assignedDriverColumn();
            if ($driverCol) {
                $query->where($driverCol, $driver->id);
            } else {
                $query->whereHas('acceptedOffer', fn ($q) => $q->where('driver_id', $driver->id));
            }
            $trip = $query->findOrFail($id);

            if ($action === '' && $request->isMethod('get')) {
                $action = in_array((string) $trip->status, ['going_to_pickup'], true)
                    ? 'picked_up'
                    : 'going_to_pickup';
            }

            if ($action === 'going_to_pickup') {
                if (!in_array((string) $trip->status, ['in_progress', 'accepted', 'driver_assigned'], true)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'لا يمكن بدء التوجه في الحالة الحالية',
                    ], 400);
                }
                try {
                    $trip->update(['status' => 'going_to_pickup']);
                } catch (\Throwable $e) {
                    // ENUM قديم بدون going_to_pickup
                }
                if ($trip->user) {
                    app(FcmService::class)->sendToUser(
                        $trip->user,
                        'السائق في الطريق',
                        'السائق في الطريق إلى موقع الاستلام.',
                        'trip_status',
                        (string) $trip->id,
                    );
                }

                return app(OrderTrackingController::class)->show('bidding_trip', $id);
            }

            if ($action === 'picked_up') {
                if (!in_array((string) $trip->status, ['in_progress', 'accepted', 'going_to_pickup', 'driver_assigned'], true)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'لا يمكن تأكيد الاستلام في الحالة الحالية',
                    ], 400);
                }
                try {
                    $trip->update(['status' => 'picked_up']);
                    $trip->refresh();
                    if ((string) $trip->status !== 'picked_up') {
                        $trip->update(['status' => 'in_progress']);
                    }
                } catch (\Throwable $e) {
                    try {
                        $trip->update(['status' => 'in_progress']);
                    } catch (\Throwable $ignored) {
                    }
                }
                if (Schema::hasColumn('bidding_trips', 'handed_to_driver')) {
                    try {
                        $trip->update(['handed_to_driver' => true]);
                    } catch (\Throwable $e) {
                    }
                }
                if ($trip->user) {
                    app(FcmService::class)->sendToUser(
                        $trip->user,
                        'تم استلام الطلب',
                        'تم استلام الطلب وجارٍ التوصيل إلى وجهة التسليم.',
                        'trip_status',
                        (string) $trip->id,
                    );
                }

                return app(OrderTrackingController::class)->show('bidding_trip', $id);
            }

            return response()->json(['status' => false, 'message' => 'إجراء غير معروف'], 422);
        }

        if ($type === 'service_order') {
            $order = ServiceOrder::where('driver_id', $driver->id)->findOrFail($id);

            if ($action === '' && $request->isMethod('get')) {
                $action = in_array((string) $order->status, ['in_progress', 'in_delivery'], true)
                    ? 'picked_up'
                    : 'going_to_pickup';
            }

            if ($action === 'going_to_pickup') {
                if (!in_array((string) $order->status, ['driver_assigned', 'preparing', 'accepted', 'in_progress'], true)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'لا يمكن بدء التوجه في الحالة الحالية',
                    ], 400);
                }
                if ($order->user) {
                    $order->load('user');
                    if ($order->user) {
                        app(FcmService::class)->sendToUser(
                            $order->user,
                            'السائق في الطريق',
                            'السائق في الطريق إلى موقع الاستلام.',
                            'service_order_status',
                            (string) $order->id,
                        );
                    }
                }

                return app(OrderTrackingController::class)->show('service_order', $id);
            }

            if ($action === 'picked_up') {
                if (!in_array((string) $order->status, ['driver_assigned', 'preparing', 'accepted', 'in_progress', 'in_delivery'], true)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'لا يمكن تأكيد الاستلام في الحالة الحالية',
                    ], 400);
                }
                $order->update(['status' => 'in_progress']);
                $order->load('user');
                if ($order->user) {
                    app(FcmService::class)->sendToUser(
                        $order->user,
                        'تم استلام الطلب',
                        'تم استلام الطلب وجارٍ التوصيل.',
                        'service_order_status',
                        (string) $order->id,
                    );
                }

                return app(OrderTrackingController::class)->show('service_order', $id);
            }

            return response()->json(['status' => false, 'message' => 'إجراء غير معروف'], 422);
        }

        return response()->json(['status' => false, 'message' => 'نوع الطلب غير مدعوم'], 404);
    }

    public function completeOrder(Request $request, string $type, int $id)
    {
        $driver = $this->requireDriver();
        $amount = 0;

        if ($type === 'ride_book') {
            $booking = RideBook::where('driver_id', $driver->id)->findOrFail($id);
            if ($booking->status === 'completed') {
                return response()->json(['status_code' => 422, 'message' => 'الطلب مكتمل مسبقاً'], 422);
            }
            $booking->update(['status' => 'completed']);
            $amount = (float) $booking->amount;
        } elseif ($type === 'trip_request') {
            $trip = TripRequest::with('user')
                ->whereIn('status', ['handed_to_driver', 'in_progress'])
                ->whereHas('acceptedOffer', fn ($q) => $q->where('driver_id', $driver->id))
                ->findOrFail($id);
            $trip->update(['status' => 'completed']);
            if ($trip->ride_book_id) {
                RideBook::where('id', $trip->ride_book_id)->update(['status' => 'completed']);
            }
            $amount = (float) optional($trip->acceptedOffer)->amount;
            if ($trip->user) {
                app(FcmService::class)->sendToUser(
                    $trip->user,
                    'تم تسليم الطلب',
                    'تم تسليم طلبك بنجاح. يمكنك الآن تقييم السائق',
                    'trip_completed',
                    (string) $trip->id,
                );
            }
        } elseif ($type === 'bidding_trip') {
            $trip = BiddingTrip::with('user')
                ->whereIn('status', ['handed_to_driver', 'in_progress', 'accepted']);
            $driverCol = BiddingTrip::assignedDriverColumn();
            if ($driverCol) {
                $trip->where($driverCol, $driver->id);
            } else {
                $trip->whereHas('acceptedOffer', fn ($q) => $q->where('driver_id', $driver->id));
            }
            $trip = $trip->findOrFail($id);
            $trip->update(['status' => 'completed']);
            if (!empty($trip->ride_book_id)) {
                RideBook::where('id', $trip->ride_book_id)->update(['status' => 'completed']);
            }
            $amount = (float) ($trip->final_price ?? 0);
            if ($trip->user) {
                app(FcmService::class)->sendToUser(
                    $trip->user,
                    'تم تسليم الطلب',
                    'تم تسليم طلبك بنجاح. يمكنك الآن تقييم السائق',
                    'bidding_completed',
                    (string) $trip->id,
                );
            }
        } elseif ($type === 'service_order') {
            $order = ServiceOrder::where('driver_id', $driver->id)->findOrFail($id);
            if ($order->status === 'completed') {
                return response()->json(['status_code' => 422, 'message' => 'الطلب مكتمل مسبقاً'], 422);
            }
            if (!in_array($order->status, ['handed_to_driver', 'driver_assigned', 'in_delivery', 'in_progress'], true)) {
                return response()->json([
                    'status_code' => 422,
                    'message' => 'لا يمكن تأكيد التسليم في هذه الحالة',
                ], 422);
            }
            $order->update(['status' => 'completed']);
            if (!empty($order->ride_book_id)) {
                RideBook::where('id', $order->ride_book_id)->update(['status' => 'completed']);
            }
            $amount = (float) ($order->delivery_fee ?? 0);
            $order->load(['merchant.owner', 'user']);
            $this->applyMerchantCommissionOnComplete($order);
            if ($order->user) {
                app(FcmService::class)->sendToUser(
                    $order->user,
                    'تم تسليم الطلب',
                    'تم توصيل طلبك من ' . ($order->merchant?->name ?? '') . '. يمكنك الآن تقييم السائق',
                    'service_order_status',
                    (string) $order->id,
                );
            }
            app(MerchantOrderNotificationService::class)->notifyOrderStatus(
                $order,
                'تم تسليم الطلب',
                'تم تسليم الطلب #' . $order->id . ' للعميل. يمكنك تقييم العميل إن رغبت',
            );
        } else {
            return response()->json(['status_code' => 422, 'message' => 'نوع غير مدعوم'], 422);
        }

        $this->applyCommission($driver, $amount);

        return response()->json([
            'status_code' => 200,
            'message' => 'تم إنهاء الطلب بنجاح',
        ]);
    }

    public function duesSummary()
    {
        $user = $this->requireDriver();

        $completedTrips = TripRequest::where('status', 'completed')
            ->whereHas('acceptedOffer', fn ($q) => $q->where('driver_id', $user->id))
            ->count();

        $completedRides = RideBook::where('driver_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $completedServices = ServiceOrder::where('driver_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $walletNumber = Schema::hasTable('settings')
            ? (Setting::getValue('wallet_number', '') ?? '')
            : '';

        $instapayNumber = Schema::hasTable('settings')
            ? (Setting::getValue('instapay_number', $walletNumber) ?? $walletNumber)
            : $walletNumber;

        return response()->json([
            'status_code' => 200,
            'data' => [
                'dues_balance' => (float) ($user->dues_balance ?? 0),
                'commission_rate' => (float) ($user->commission_rate ?? 10),
                'completed_orders_count' => $completedTrips + $completedRides + $completedServices,
                'wallet_number' => $walletNumber,
                'instapay_number' => $instapayNumber,
                'billing_mode' => DriverBilling::resolveMode($user),
                'commission_option_enabled' => DriverBilling::commissionOptionEnabled(),
                'fixed_fee_option_enabled' => DriverBilling::fixedFeeOptionEnabled(),
                'order_commission_percent' => DriverBilling::commissionPercent($user),
                'order_fixed_fee' => DriverBilling::fixedFeeAmount(),
                'fixed_fee_per_order' => DriverBilling::fixedFeeAmount(),
            ],
        ]);
    }

    public function submitSettlement(Request $request)
    {
        $driver = $this->requireDriver();

        $data = $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $balance = (float) ($driver->dues_balance ?? 0);
        if ($balance <= 0) {
            return response()->json([
                'status_code' => 422,
                'message' => 'لا يوجد رصيد مستحق للسداد',
            ], 422);
        }

        $proof = $request->file('payment_proof')->store('settlements', 'public');

        $hasPending = SettlementRequest::where('user_id', $driver->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'status_code' => 422,
                'message' => 'لديك طلب سداد بانتظار مراجعة الإدارة',
            ], 422);
        }

        SettlementRequest::create([
            'user_id' => $driver->id,
            'account_type' => 'driver',
            'amount' => $balance,
            'payment_proof' => $proof,
            'status' => 'pending',
        ]);

        return response()->json([
            'status_code' => 201,
            'message' => 'تم إرسال طلب السداد وبانتظار مراجعة الإدارة',
        ], 201);
    }

    /**
     * Driver Interface: available bids (includes bidding trips + panel orders).
     */
    public function availableBids(Request $request)
    {
        $base = $this->availableOrders($request);
        $payload = $base->getData(true);
        $orders = $payload['data'] ?? [];

        if (Schema::hasTable('bidding_trips')) {
            try {
            $driver = $this->requireDriver();
            \App\Models\BiddingTrip::ensureSchema();
            $matchKeys = \App\Models\VehicleType::matchingKeys($driver->vehicle_type);
            $proximity = app(DriverProximityService::class);

            // بدون وسيلة نقل محددة لا تُعرض أي طلبات — وإلا ظهرت لكل السائقين.
            $biddingTrips = $matchKeys === []
                ? collect()
                : \App\Models\BiddingTrip::query()
                    ->with('user')
                    ->where('status', 'pending')
                    ->whereIn('vehicle_type', $matchKeys)
                    ->where('user_id', '!=', $driver->id)
                    ->orderByDesc('created_at')
                    ->limit(80)
                    ->get()
                    ->filter(function ($trip) use ($driver, $proximity) {
                        return $proximity->driverSeesPickup(
                            $driver,
                            $trip->pickup_lat !== null ? (float) $trip->pickup_lat : null,
                            $trip->pickup_lng !== null ? (float) $trip->pickup_lng : null,
                            $trip->vehicle_type
                        );
                    });

            $driverLat = Schema::hasColumn('users', 'current_lat') && $driver->current_lat !== null
                ? (float) $driver->current_lat
                : null;
            $driverLng = Schema::hasColumn('users', 'current_lng') && $driver->current_lng !== null
                ? (float) $driver->current_lng
                : null;

            foreach ($biddingTrips as $trip) {
                $myOffer = null;
                if (Schema::hasTable('bidding_offers')) {
                    $myOffer = \App\Models\BiddingOffer::where('bidding_trip_id', $trip->id)
                        ->where('driver_id', $driver->id)
                        ->first();
                }

                $pickupLat = $trip->pickup_lat !== null ? (float) $trip->pickup_lat : null;
                $pickupLng = $trip->pickup_lng !== null ? (float) $trip->pickup_lng : null;
                $dropoffLat = $trip->dropoff_lat !== null ? (float) $trip->dropoff_lat : null;
                $dropoffLng = $trip->dropoff_lng !== null ? (float) $trip->dropoff_lng : null;

                $toPickupKm = $proximity->distanceKm($driverLat, $driverLng, $pickupLat, $pickupLng);
                $routeKm = $proximity->distanceKm($pickupLat, $pickupLng, $dropoffLat, $dropoffLng);
                $customerPrice = (float) ($trip->customer_price ?? 0);

                $orders[] = [
                    'id' => $trip->id,
                    'type' => 'bidding_trip',
                    'title' => 'طلب مزايدة',
                    'pick_loc' => $trip->pickup_address,
                    'target_loc' => $trip->dropoff_address,
                    'pick_lat' => $pickupLat,
                    'pick_lng' => $pickupLng,
                    'target_lat' => $dropoffLat,
                    'target_lng' => $dropoffLng,
                    'distance_km' => $toPickupKm,
                    'distance_label' => $toPickupKm !== null
                        ? number_format($toPickupKm, 1) . ' كم'
                        : null,
                    'route_distance_km' => $routeKm,
                    'route_distance_label' => $routeKm !== null
                        ? number_format($routeKm, 1) . ' كم'
                        : null,
                    'description' => $trip->description,
                    'status' => $trip->status,
                    'can_offer' => $myOffer === null,
                    'customer_price' => $customerPrice > 0 ? $customerPrice : null,
                    'amount' => $customerPrice > 0 ? $customerPrice : null,
                    'my_offer_amount' => $myOffer?->amount(),
                    'offer_status' => $myOffer?->status,
                    'show_contact' => false,
                    'customer_id' => $trip->user_id,
                    'customer_name' => $trip->user?->name,
                    'customer_rating' => round((float) ($trip->user?->avg_rating ?? 0), 1),
                    'customer_ratings_count' => (int) ($trip->user?->ratings_count ?? 0),
                    'created_at' => optional($trip->created_at)->format('Y-m-d H:i'),
                ];
            }

            // Accepted bidding trips for this driver
            if (Schema::hasTable('bidding_offers')) {
                try {
                    \App\Models\BiddingTrip::ensureSchema();
                    $acceptedQuery = \App\Models\BiddingTrip::query()
                        ->with('user')
                        ->whereIn('status', ['accepted', 'in_progress', 'handed_to_driver']);

                    $acceptedQuery->where(function ($q) use ($driver) {
                        $driverCol = \App\Models\BiddingTrip::assignedDriverColumn();
                        if ($driverCol) {
                            $q->where($driverCol, $driver->id);
                        }
                        if (Schema::hasColumn('bidding_trips', 'accepted_offer_id')) {
                            $q->orWhereHas('acceptedOffer', fn ($oq) => $oq->where('driver_id', $driver->id));
                        }
                    });

                    $accepted = $acceptedQuery
                        ->orderByDesc('updated_at')
                        ->limit(20)
                        ->get();

                    foreach ($accepted as $trip) {
                        $orders[] = [
                            'id' => $trip->id,
                            'type' => 'bidding_trip',
                            'title' => 'مزايدة مقبولة',
                            'pick_loc' => $trip->pickup_address,
                            'target_loc' => $trip->dropoff_address,
                            'pick_lat' => $trip->pickup_lat !== null ? (float) $trip->pickup_lat : null,
                            'pick_lng' => $trip->pickup_lng !== null ? (float) $trip->pickup_lng : null,
                            'target_lat' => $trip->dropoff_lat !== null ? (float) $trip->dropoff_lat : null,
                            'target_lng' => $trip->dropoff_lng !== null ? (float) $trip->dropoff_lng : null,
                            'distance_label' => null,
                            'description' => $trip->description,
                            'status' => $trip->status,
                            'amount' => $trip->final_price !== null ? (float) $trip->final_price : null,
                            'can_offer' => false,
                            'offer_status' => 'accepted',
                            'show_contact' => true,
                            'customer_name' => $trip->user?->name,
                            'customer_phone' => $trip->phone ?? $trip->user?->phone,
                            'customer_id' => $trip->user_id,
                            'created_at' => optional($trip->created_at)->format('Y-m-d H:i'),
                        ];
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('availableBids accepted trips: '.$e->getMessage());
                }
            }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('availableBids bidding trips: '.$e->getMessage());
            }
        }

        usort($orders, fn ($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        return response()->json([
            'status_code' => 200,
            'data' => array_values($orders),
        ]);
    }

    public function makeOffer(Request $request, int $id)
    {
        $type = $request->input('type', 'trip_request');
        $amount = $request->input('amount', $request->input('proposed_price'));

        if ($amount === null) {
            return response()->json([
                'status_code' => 422,
                'message' => 'السعر مطلوب',
            ], 422);
        }

        $request->merge([
            'amount' => $amount,
            'proposed_price' => $amount,
        ]);

        if ($type === 'bidding_trip') {
            return app(ApiBiddingController::class)->submitOffer($request, $id);
        }

        if ($type === 'service_order') {
            return app(LocalServiceController::class)->submitDeliveryOffer($request, $id);
        }

        return app(TripRequestController::class)->submitOffer($request, $id);
    }

    public function myTrips()
    {
        $completed = $this->myCompletedOrders();
        $payload = $completed->getData(true);
        $items = $payload['data'] ?? [];

        // Normalize titles for UI
        foreach ($items as &$item) {
            if (empty($item['title'])) {
                $item['title'] = match ($item['type'] ?? '') {
                    'ride_book' => 'حجز مكتمل',
                    'service_order' => 'توصيل مكتمل',
                    'trip_request' => 'مشوار مكتمل',
                    'bidding_trip' => 'مزايدة مكتملة',
                    default => 'طلب مكتمل',
                };
            }
        }
        unset($item);

        return response()->json([
            'status_code' => 200,
            'data' => array_values($items),
        ]);
    }

    public function wallet()
    {
        $response = $this->duesSummary();
        $payload = $response->getData(true);
        $data = $payload['data'] ?? [];

        return response()->json([
            'status_code' => 200,
            'data' => [
                'due_amount' => (float) ($data['dues_balance'] ?? 0),
                'dues_balance' => (float) ($data['dues_balance'] ?? 0),
                'commission_rate' => (float) ($data['commission_rate'] ?? 10),
                'completed_trips_count' => (int) ($data['completed_orders_count'] ?? 0),
                'completed_orders_count' => (int) ($data['completed_orders_count'] ?? 0),
                'wallet_number' => $data['wallet_number'] ?? '',
                'instapay_number' => $data['instapay_number'] ?? '',
                'billing_mode' => $data['billing_mode'] ?? null,
                'commission_option_enabled' => (bool) ($data['commission_option_enabled'] ?? false),
                'fixed_fee_option_enabled' => (bool) ($data['fixed_fee_option_enabled'] ?? false),
                'order_commission_percent' => (float) ($data['order_commission_percent'] ?? 0),
                'order_fixed_fee' => (float) ($data['order_fixed_fee'] ?? 0),
                'fixed_fee_per_order' => (float) ($data['fixed_fee_per_order'] ?? 0),
            ],
        ]);
    }

    public function updateBillingMode(Request $request)
    {
        $data = $request->validate([
            'billing_mode' => 'required|in:commission,fixed_fee',
        ]);

        $available = $data['billing_mode'] === 'fixed_fee'
            ? DriverBilling::fixedFeeOptionEnabled()
            : DriverBilling::commissionOptionEnabled();

        if (!$available) {
            return response()->json([
                'status_code' => 422,
                'message' => 'هذا الخيار غير متاح حالياً',
            ], 422);
        }

        $driver = $this->requireDriver();
        if (Schema::hasColumn('users', 'vendor_billing_mode')) {
            $driver->vendor_billing_mode = $data['billing_mode'];
            $driver->save();
        }

        return $this->wallet();
    }

    public function settleWallet(Request $request)
    {
        if ($request->hasFile('receipt_image') && !$request->hasFile('payment_proof')) {
            $request->files->set('payment_proof', $request->file('receipt_image'));
        }

        return $this->submitSettlement($request);
    }

    private function requireDriver(): User
    {
        $user = Auth::guard('api')->user();
        if ($user->title !== 'driver') {
            abort(response()->json(['status_code' => 403, 'message' => 'للسائقين فقط'], 403));
        }

        return $user;
    }

    private function applyCommission(User $driver, float $amount): void
    {
        $commission = DriverBilling::dueAmount($driver, $amount);
        if ($commission <= 0) {
            return;
        }

        $driver->increment('dues_balance', $commission);
    }

    private function applyMerchantCommissionOnComplete(ServiceOrder $order): void
    {
        if (Schema::hasColumn('service_orders', 'commission_applied') && $order->commission_applied) {
            return;
        }

        $owner = $order->merchant?->owner;
        $orderAmount = (float) ($order->total_price ?? $order->product_price ?? 0);
        $commission = \App\Support\VendorOrderBilling::dueAmountForMerchant($order->merchant, $orderAmount);
        if ($commission <= 0 || !$owner) {
            return;
        }

        $owner->increment('dues_balance', $commission);

        $updates = [];
        if (Schema::hasColumn('service_orders', 'commission_amount')) {
            $updates['commission_amount'] = $commission;
        }
        if (Schema::hasColumn('service_orders', 'commission_applied')) {
            $updates['commission_applied'] = true;
        }
        if ($updates !== []) {
            $order->update($updates);
        }
    }

    private function formatCompleted(
        string $type,
        int $id,
        ?string $pick,
        ?string $target,
        ?string $desc,
        $amount,
        $date,
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'pick_loc' => $pick,
            'target_loc' => $target,
            'description' => $desc,
            'amount' => $amount,
            'completed_at' => optional($date)->format('Y-m-d H:i'),
        ];
    }

    private function normalizeVehicle(?string $vehicleType): string
    {
        $vehicleType = strtolower((string) $vehicleType);

        return $vehicleType === 'toktok' ? 'tricycle' : $vehicleType;
    }
}
