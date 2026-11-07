<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BiddingTrip;
use App\Models\Merchant;
use App\Models\Rating;
use App\Models\ServiceOrder;
use App\Models\TripRequest;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RatingController extends Controller
{
    public function store(Request $request, RatingService $ratings)
    {
        if (!Schema::hasTable('ratings')) {
            return response()->json([
                'status' => 'error',
                'message' => 'نظام التقييمات غير مفعّل. نفّذ php artisan migrate',
            ], 500);
        }

        $data = $request->validate([
            'score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
            'context_type' => 'required|in:bidding_trip,trip_request,service_order',
            'context_id' => 'required|integer|min:1',
            'ratee_user_id' => 'nullable|integer|exists:users,id',
            'ratee_merchant_id' => 'nullable|integer',
        ]);

        if (empty($data['ratee_user_id']) && empty($data['ratee_merchant_id'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'يجب تحديد من سيتم تقييمه',
            ], 422);
        }

        $rater = Auth::guard('api')->user();
        $allowed = $this->assertCanRate($rater, $data);
        if ($allowed !== true) {
            return $allowed;
        }

        $rating = Rating::updateOrCreate(
            [
                'rater_id' => $rater->id,
                'context_type' => $data['context_type'],
                'context_id' => $data['context_id'],
                'ratee_user_id' => $data['ratee_user_id'] ?? null,
                'ratee_merchant_id' => $data['ratee_merchant_id'] ?? null,
            ],
            [
                'score' => $data['score'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        if (!empty($data['ratee_user_id'])) {
            $ratings->recalculateUser((int) $data['ratee_user_id']);
        }
        if (!empty($data['ratee_merchant_id'])) {
            $ratings->recalculateMerchant((int) $data['ratee_merchant_id']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ التقييم بنجاح',
            'data' => [
                'id' => $rating->id,
                'score' => $rating->score,
            ],
        ], 201);
    }

    /**
     * @return true|\Illuminate\Http\JsonResponse
     */
    private function assertCanRate(User $rater, array $data)
    {
        $type = $data['context_type'];
        $id = (int) $data['context_id'];

        if ($type === 'bidding_trip') {
            $trip = BiddingTrip::find($id);
            if (!$trip || $trip->status !== 'completed') {
                return $this->deny('يمكن التقييم بعد اكتمال الرحلة فقط');
            }
            $assignedDriverId = $trip->assignedDriverId();
            $isCustomer = (int) $trip->user_id === (int) $rater->id;
            $isDriver = $assignedDriverId !== null && $assignedDriverId === (int) $rater->id;
            if (!$isCustomer && !$isDriver) {
                return $this->deny('غير مسموح بالتقييم على هذه الرحلة');
            }
            if (!empty($data['ratee_user_id'])) {
                $target = (int) $data['ratee_user_id'];
                if ($isCustomer && $assignedDriverId !== null && $target !== $assignedDriverId) {
                    return $this->deny('يمكنك تقييم السائق فقط');
                }
                if ($isDriver && $target !== (int) $trip->user_id) {
                    return $this->deny('يمكنك تقييم العميل فقط');
                }
            }
            return true;
        }

        if ($type === 'trip_request') {
            $trip = TripRequest::with('acceptedOffer')->find($id);
            if (!$trip || $trip->status !== 'completed') {
                return $this->deny('يمكن التقييم بعد اكتمال الطلب فقط');
            }
            $driverId = (int) optional($trip->acceptedOffer)->driver_id;
            $isCustomer = (int) $trip->user_id === (int) $rater->id;
            $isDriver = $driverId === (int) $rater->id;
            if (!$isCustomer && !$isDriver) {
                return $this->deny('غير مسموح بالتقييم على هذا الطلب');
            }
            if (!empty($data['ratee_user_id'])) {
                $target = (int) $data['ratee_user_id'];
                if ($isCustomer && $target !== $driverId) {
                    return $this->deny('يمكنك تقييم السائق فقط');
                }
                if ($isDriver && $target !== (int) $trip->user_id) {
                    return $this->deny('يمكنك تقييم العميل فقط');
                }
            }
            return true;
        }

        if ($type === 'service_order') {
            $order = ServiceOrder::find($id);
            if (!$order || $order->status !== 'completed') {
                return $this->deny('يمكن التقييم بعد اكتمال الطلب فقط');
            }
            $isCustomer = (int) $order->user_id === (int) $rater->id;
            $isDriver = (int) ($order->driver_id ?? 0) === (int) $rater->id;
            $isMerchantOwner = false;
            if ($order->merchant_id) {
                $merchant = Merchant::find($order->merchant_id);
                $isMerchantOwner = $merchant && (int) $merchant->owner_user_id === (int) $rater->id;
            }

            if (!$isCustomer && !$isDriver && !$isMerchantOwner) {
                return $this->deny('غير مسموح بالتقييم على هذا الطلب');
            }

            if (!empty($data['ratee_merchant_id'])) {
                if (!$isCustomer || (int) $data['ratee_merchant_id'] !== (int) $order->merchant_id) {
                    return $this->deny('يمكنك تقييم المتجر فقط بعد اكتمال طلبك');
                }
            }

            if (!empty($data['ratee_user_id'])) {
                $target = (int) $data['ratee_user_id'];
                if ($isCustomer && $target !== (int) $order->driver_id) {
                    return $this->deny('يمكنك تقييم السائق فقط');
                }
                if (($isDriver || $isMerchantOwner) && $target !== (int) $order->user_id) {
                    return $this->deny('يمكنك تقييم العميل فقط');
                }
            }

            return true;
        }

        return $this->deny('نوع التقييم غير مدعوم');
    }

    private function deny(string $message)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], 403);
    }
}
