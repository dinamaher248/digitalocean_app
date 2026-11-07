<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BiddingTrip;
use App\Models\RideBook;
use App\Models\ServiceOrder;
use App\Models\TripRequest;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class OrderTrackingController extends Controller
{
    public function show(string $type, int $id)
    {
        $user = Auth::guard('api')->user();
        $ctx = $this->resolve($type, $id, $user);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) {
            return $ctx;
        }

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'data' => $this->payload($ctx),
        ]);
    }

    public function handToDriver(string $type, int $id)
    {
        $user = Auth::guard('api')->user();
        $ctx = $this->resolve($type, $id, $user);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) {
            return $ctx;
        }

        if (!$ctx['is_customer_side']) {
            return response()->json([
                'status' => false,
                'message' => 'غير مسموح',
            ], 403);
        }

        if ($ctx['status'] !== 'in_progress' && $ctx['status'] !== 'driver_assigned') {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن تأكيد التسليم للسائق في هذه الحالة',
            ], 422);
        }

        $this->setStatus($ctx, 'handed_to_driver');

        if ($ctx['driver']) {
            try {
                app(FcmService::class)->sendToUser(
                    $ctx['driver'],
                    'تم تسليم الطلب لك',
                    'أكد العميل/التاجر تسليم الطلب، يمكنك بدء التوصيل',
                    'order_handed',
                    (string) $id,
                );
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $ctx = $this->resolve($type, $id, $user);
        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'تم تأكيد تسليم الطلب للسائق',
            'data' => $this->payload($ctx),
        ]);
    }

    public function cancelActive(string $type, int $id)
    {
        $user = Auth::guard('api')->user();
        $ctx = $this->resolve($type, $id, $user);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) {
            return $ctx;
        }

        if (!$ctx['is_customer_side']) {
            return response()->json(['status' => false, 'message' => 'غير مسموح'], 403);
        }

        if (!in_array($ctx['status'], ['in_progress', 'driver_assigned'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن إنهاء الطلب بعد تسليمه للسائق',
            ], 422);
        }

        $this->setStatus($ctx, 'cancelled');

        if ($ctx['driver']) {
            try {
                app(FcmService::class)->sendToUser(
                    $ctx['driver'],
                    'تم إنهاء الطلب',
                    'أنهى العميل/التاجر الطلب رقم ' . $id,
                    'order_cancelled',
                    (string) $id,
                );
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $ctx = $this->resolve($type, $id, $user);
        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'تم إنهاء الطلب',
            'data' => $this->payload($ctx),
        ]);
    }

    /**
     * @return array|\Illuminate\Http\JsonResponse
     */
    private function resolve(string $type, int $id, User $user)
    {
        if ($type === 'trip_request') {
            $trip = TripRequest::with(['user', 'acceptedOffer.driver'])->find($id);
            if (!$trip) {
                return response()->json(['status' => false, 'message' => 'الطلب غير موجود'], 404);
            }
            $driver = $trip->acceptedOffer?->driver;
            $isCustomer = (int) $trip->user_id === (int) $user->id;
            $isDriver = $driver && (int) $driver->id === (int) $user->id;
            if (!$isCustomer && !$isDriver) {
                return response()->json(['status' => false, 'message' => 'غير مسموح'], 403);
            }
            $status = $this->normalizeStatus($trip->status);
            return [
                'type' => 'trip_request',
                'model' => $trip,
                'status' => $status,
                'is_customer_side' => $isCustomer,
                'is_driver' => $isDriver,
                'driver' => $driver,
                'customer' => $trip->user,
                'price' => (float) optional($trip->acceptedOffer)->amount,
                'pickup' => $trip->pick_loc,
                'dropoff' => $trip->target_loc,
                'pickup_lat' => $trip->pick_lat,
                'pickup_lng' => $trip->pick_lng,
                'dropoff_lat' => $trip->target_lat,
                'dropoff_lng' => $trip->target_lng,
                'ride_book_id' => $trip->ride_book_id,
                'driver_id' => $driver?->id,
                'partner_user_id' => $isCustomer ? $driver?->id : $trip->user_id,
            ];
        }

        if ($type === 'bidding_trip') {
            $trip = BiddingTrip::with(['user', 'driver', 'acceptedOffer.driver'])->find($id);
            if (!$trip) {
                return response()->json(['status' => false, 'message' => 'الطلب غير موجود'], 404);
            }
            $driver = $trip->driver ?? $trip->acceptedOffer?->driver;
            $isCustomer = (int) $trip->user_id === (int) $user->id;
            $isDriver = $driver && (int) $driver->id === (int) $user->id;
            if (!$isCustomer && !$isDriver) {
                return response()->json(['status' => false, 'message' => 'غير مسموح'], 403);
            }
            return [
                'type' => 'bidding_trip',
                'model' => $trip,
                'status' => $this->normalizeStatus($trip->status),
                'is_customer_side' => $isCustomer,
                'is_driver' => $isDriver,
                'driver' => $driver,
                'customer' => $trip->user,
                'price' => (float) ($trip->final_price ?? optional($trip->acceptedOffer)->amount()),
                'pickup' => $trip->pickup_address,
                'dropoff' => $trip->dropoff_address,
                'pickup_lat' => $trip->pickup_lat,
                'pickup_lng' => $trip->pickup_lng,
                'dropoff_lat' => $trip->dropoff_lat,
                'dropoff_lng' => $trip->dropoff_lng,
                'ride_book_id' => $trip->ride_book_id ?? null,
                'driver_id' => $driver?->id,
                'partner_user_id' => $isCustomer ? $driver?->id : $trip->user_id,
            ];
        }

        if ($type === 'service_order') {
            $order = ServiceOrder::with(['user', 'driver', 'merchant'])->find($id);
            if (!$order) {
                return response()->json(['status' => false, 'message' => 'الطلب غير موجود'], 404);
            }
            $driver = $order->driver;
            $merchant = $order->merchant;
            $isCustomer = (int) $order->user_id === (int) $user->id;
            $isMerchant = $merchant && (int) ($merchant->owner_user_id ?? 0) === (int) $user->id;
            $isDriver = $driver && (int) $driver->id === (int) $user->id;
            if (!$isCustomer && !$isMerchant && !$isDriver) {
                return response()->json(['status' => false, 'message' => 'غير مسموح'], 403);
            }

            $status = $this->normalizeStatus($order->status);
            // Treat assigned delivery as in_progress for UI
            if (in_array($order->status, ['driver_assigned', 'in_delivery'], true)) {
                $status = 'in_progress';
            }

            return [
                'type' => 'service_order',
                'model' => $order,
                'status' => $status,
                'is_customer_side' => $isCustomer || $isMerchant,
                'is_driver' => $isDriver,
                'driver' => $driver,
                'customer' => $order->user,
                'price' => (float) ($order->delivery_fee ?? $order->merchant_offer_price ?? $order->total_price ?? 0),
                'pickup' => $merchant?->name ?? $merchant?->address ?? 'الاستلام',
                'dropoff' => $order->delivery_address,
                'pickup_lat' => $merchant?->lat,
                'pickup_lng' => $merchant?->lng,
                'dropoff_lat' => $order->delivery_lat,
                'dropoff_lng' => $order->delivery_lng,
                'ride_book_id' => $order->ride_book_id ?? null,
                'driver_id' => $driver?->id,
                'partner_user_id' => ($isCustomer || $isMerchant) ? $driver?->id : $order->user_id,
                'merchant_id' => $order->merchant_id,
            ];
        }

        return response()->json(['status' => false, 'message' => 'نوع غير مدعوم'], 422);
    }

    private function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'accepted', 'active' => 'in_progress',
            'done' => 'completed',
            'canceled' => 'cancelled',
            default => (string) $status,
        };
    }

    private function setStatus(array $ctx, string $status): void
    {
        /** @var TripRequest|BiddingTrip|ServiceOrder $model */
        $model = $ctx['model'];
        $model->update(['status' => $status]);

        $rideBookId = $ctx['ride_book_id'] ?? null;
        if ($rideBookId && Schema::hasTable('ride_book')) {
            $rideStatus = $status === 'handed_to_driver' ? 'in_progress' : $status;
            RideBook::where('id', $rideBookId)->update(['status' => $rideStatus]);
        }
    }

    private function payload(array $ctx): array
    {
        $driver = $ctx['driver'];
        $status = $ctx['status'];
        $handed = $status === 'handed_to_driver';
        $active = in_array($status, ['in_progress', 'driver_assigned', 'handed_to_driver'], true);
        $completed = $status === 'completed';

        $driverLat = $driver?->current_lat ?? $driver?->last_lat;
        $driverLng = $driver?->current_lng ?? $driver?->last_lng;
        $customer = $ctx['customer'] ?? null;
        $model = $ctx['model'];
        $orderPhone = $model->phone ?? $model->contact_phone ?? $model->customer_phone ?? null;
        $contactPhone = $this->resolveContactPhone($orderPhone, $customer?->phone);
        $driverPhone = $this->resolveContactPhone($driver?->phone, null);
        $isDriver = !empty($ctx['is_driver']);
        $isCustomerSide = !empty($ctx['is_customer_side']);
        $canCall = $active && !$completed;

        return [
            'type' => $ctx['type'],
            'id' => $ctx['model']->id,
            'status' => $status,
            'status_label' => match ($status) {
                'in_progress', 'driver_assigned' => 'جاري التنفيذ',
                'handed_to_driver' => 'تم التسليم للسائق',
                'completed' => 'مكتمل',
                'cancelled' => 'ملغي',
                default => $status,
            },
            'price' => $ctx['price'],
            'pickup_address' => $ctx['pickup'],
            'dropoff_address' => $ctx['dropoff'],
            'pickup_lat' => $ctx['pickup_lat'] !== null ? (float) $ctx['pickup_lat'] : null,
            'pickup_lng' => $ctx['pickup_lng'] !== null ? (float) $ctx['pickup_lng'] : null,
            'dropoff_lat' => $ctx['dropoff_lat'] !== null ? (float) $ctx['dropoff_lat'] : null,
            'dropoff_lng' => $ctx['dropoff_lng'] !== null ? (float) $ctx['dropoff_lng'] : null,
            'driver' => $driver ? [
                'id' => $driver->id,
                'name' => $driver->name,
                'image' => $driver->image,
                'phone' => ($canCall && $isCustomerSide) ? $driverPhone : null,
                'rating' => round((float) ($driver->avg_rating ?? 0), 1),
                'ratings_count' => (int) ($driver->ratings_count ?? 0),
                'lat' => $driverLat !== null ? (float) $driverLat : null,
                'lng' => $driverLng !== null ? (float) $driverLng : null,
            ] : null,
            'customer' => [
                'id' => $customer?->id ?? 0,
                'name' => $customer?->name ?: 'عميل',
                'image' => $customer?->image ?? null,
                'phone' => ($canCall && $isDriver) ? $contactPhone : null,
            ],
            'contact_phone' => ($canCall && $isDriver) ? $contactPhone : null,
            'customer_phone' => ($canCall && $isDriver) ? $contactPhone : null,
            'ride_book_id' => $ctx['ride_book_id'],
            'partner_user_id' => $ctx['partner_user_id'],
            'viewer_role' => $isDriver ? 'driver' : ($isCustomerSide ? 'customer' : 'other'),
            'can_chat' => $active && !empty($ctx['ride_book_id']) && !empty($ctx['partner_user_id']),
            'can_call' => $canCall && ($isDriver ? $contactPhone !== null : $driverPhone !== null),
            'can_hand_to_driver' => $ctx['is_customer_side'] && in_array($status, ['in_progress', 'driver_assigned'], true),
            'can_end_order' => $ctx['is_customer_side'] && in_array($status, ['in_progress', 'driver_assigned'], true),
            'can_confirm_delivery' => $ctx['is_driver'] && $active,
            'handed_to_driver' => $handed,
            'is_completed' => $completed,
            'ratee_user_id' => $ctx['is_customer_side']
                ? ($ctx['driver_id'] ?? null)
                : ($ctx['customer']?->id ?? null),
            'merchant_id' => $ctx['merchant_id'] ?? null,
        ];
    }

    private function resolveContactPhone(mixed $orderPhone, mixed $accountPhone): ?string
    {
        foreach ([$orderPhone, $accountPhone] as $raw) {
            $phone = trim((string) ($raw ?? ''));
            if ($phone !== '' && strcasecmp($phone, 'null') !== 0) {
                return $phone;
            }
        }

        return null;
    }
}

