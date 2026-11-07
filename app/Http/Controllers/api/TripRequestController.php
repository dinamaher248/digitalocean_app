<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\RideBook;
use App\Models\TripOffer;
use App\Models\TripRequest;
use App\Models\User;
use App\Services\FcmService;
use App\Services\DriverProximityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TripRequestController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        if ($user->title !== 'user') {
            return response()->json([
                'status_code' => 403,
                'message' => 'هذه الخدمة متاحة للعملاء فقط',
            ], 403);
        }

        $data = $request->validate([
            'service_type' => 'required|in:quick_trip,goods',
            'vehicle_type' => [
                'required',
                'string',
                'max:50',
                Rule::exists('vehicle_types', 'key'),
            ],
            'pick_loc' => 'required|string|max:500',
            'target_loc' => 'required|string|max:500',
            'pick_lat' => 'nullable|numeric',
            'pick_lng' => 'nullable|numeric',
            'target_lat' => 'nullable|numeric',
            'target_lng' => 'nullable|numeric',
            'description' => 'required|string|max:2000',
            'contact_phone' => 'required|string|max:30',
            'notes' => 'nullable|string|max:2000',
        ]);

        $distanceKm = $this->calculateDistanceKm(
            $data['pick_lat'] ?? null,
            $data['pick_lng'] ?? null,
            $data['target_lat'] ?? null,
            $data['target_lng'] ?? null,
        );

        $tripRequest = TripRequest::create([
            ...$data,
            'user_id' => $user->id,
            'distance_km' => $distanceKm,
            'status' => 'awaiting_offers',
        ]);

        $this->notifyDriversAboutNewTrip($tripRequest);

        return response()->json([
            'status_code' => 201,
            'message' => 'تم إرسال الطلب إلى السائقين المتاحين',
            'data' => $this->formatTripRequestForCustomer($tripRequest->fresh('offers')),
        ], 201);
    }

    public function indexForDriver(Request $request)
    {
        $driver = Auth::guard('api')->user();

        if ($driver->title !== 'driver') {
            return response()->json([
                'status_code' => 403,
                'message' => 'هذه الخدمة متاحة للسائقين فقط',
            ], 403);
        }

        if (!(bool) $driver->is_online) {
            return response()->json([
                'status_code' => 200,
                'data' => [],
            ]);
        }

        $driverVehicle = $this->normalizeVehicleType($driver->vehicle_type);
        $proximity = app(DriverProximityService::class);

        $requests = TripRequest::query()
            ->where('status', 'awaiting_offers')
            ->where('vehicle_type', $driverVehicle)
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
            })
            ->values()
            ->take(50);

        return response()->json([
            'status_code' => 200,
            'data' => $requests->map(fn (TripRequest $trip) => $this->formatTripRequestForDriver($trip))->values(),
        ]);
    }

    public function myRequests()
    {
        $user = Auth::guard('api')->user();

        $requests = TripRequest::query()
            ->where('user_id', $user->id)
            ->with(['offers.driver', 'acceptedOffer.driver'])
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return response()->json([
            'status_code' => 200,
            'data' => $requests->map(fn (TripRequest $trip) => $this->formatTripRequestForCustomer($trip))->values(),
        ]);
    }

    public function show(int $id)
    {
        $user = Auth::guard('api')->user();
        $trip = TripRequest::with(['offers.driver', 'acceptedOffer.driver', 'user'])->findOrFail($id);

        if ($user->title === 'user' && (int) $trip->user_id !== (int) $user->id) {
            return response()->json(['status_code' => 403, 'message' => 'غير مسموح'], 403);
        }

        if ($user->title === 'driver') {
            $hasOffer = $trip->offers()->where('driver_id', $user->id)->exists();
            $isAcceptedDriver = (int) optional($trip->acceptedOffer)->driver_id === (int) $user->id;

            if (!$hasOffer && !$isAcceptedDriver && $trip->status === 'awaiting_offers') {
                return response()->json([
                    'status_code' => 200,
                    'data' => $this->formatTripRequestForDriver($trip),
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'data' => $this->formatTripRequestForDriverAfterAccept($trip, $user),
            ]);
        }

        return response()->json([
            'status_code' => 200,
            'data' => $this->formatTripRequestForCustomer($trip),
        ]);
    }

    public function offers(int $id)
    {
        $user = Auth::guard('api')->user();
        $trip = TripRequest::with('offers.driver')->findOrFail($id);

        if ((int) $trip->user_id !== (int) $user->id) {
            return response()->json(['status_code' => 403, 'message' => 'غير مسموح'], 403);
        }

        $offers = $trip->offers()
            ->where('status', '!=', 'rejected')
            ->with('driver')
            ->orderBy('amount')
            ->get();

        $showContact = in_array($trip->status, ['in_progress', 'completed'], true);

        return response()->json([
            'status_code' => 200,
            'trip' => $this->formatTripRequestForCustomer($trip),
            'data' => $offers->map(fn (TripOffer $offer) => $this->formatOfferForCustomer($offer, $showContact))->values(),
        ]);
    }

    public function submitOffer(Request $request, int $id)
    {
        $driver = Auth::guard('api')->user();

        if ($driver->title !== 'driver') {
            return response()->json(['status_code' => 403, 'message' => 'للسائقين فقط'], 403);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $trip = TripRequest::findOrFail($id);

        if ($trip->status !== 'awaiting_offers') {
            return response()->json(['status_code' => 422, 'message' => 'الطلب لم يعد متاحًا'], 422);
        }

        if ($this->normalizeVehicleType($driver->vehicle_type) !== $trip->vehicle_type) {
            return response()->json(['status_code' => 422, 'message' => 'نوع مركبتك لا يطابق الطلب'], 422);
        }

        $offer = TripOffer::updateOrCreate(
            [
                'trip_request_id' => $trip->id,
                'driver_id' => $driver->id,
            ],
            [
                'amount' => $data['amount'],
                'status' => 'pending',
            ]
        );

        $customer = User::find($trip->user_id);
        if ($customer) {
            app(FcmService::class)->sendToUser(
                $customer,
                'عرض سعر جديد',
                'قدّم السائق ' . ($driver->name ?? '') . ' عرضًا بقيمة ' . $data['amount'] . ' جنيه',
                'trip_offer',
                (string) $trip->id,
            );
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'تم إرسال العرض بنجاح',
            'data' => $this->formatOfferForCustomer($offer->fresh('driver'), false),
        ]);
    }

    public function acceptOffer(int $tripId, int $offerId)
    {
        $user = Auth::guard('api')->user();
        $trip = TripRequest::with('offers')->findOrFail($tripId);

        if ((int) $trip->user_id !== (int) $user->id) {
            return response()->json(['status_code' => 403, 'message' => 'غير مسموح'], 403);
        }

        if ($trip->status !== 'awaiting_offers') {
            return response()->json(['status_code' => 422, 'message' => 'تم قبول عرض مسبقًا'], 422);
        }

        $offer = TripOffer::where('trip_request_id', $trip->id)
            ->where('id', $offerId)
            ->where('status', 'pending')
            ->firstOrFail();

        $booking = DB::transaction(function () use ($trip, $offer, $user) {
            $offer->update(['status' => 'accepted']);
            $trip->offers()
                ->where('id', '!=', $offer->id)
                ->update(['status' => 'rejected']);

            $booking = RideBook::create([
                'driver_id' => $offer->driver_id,
                'user_id' => $user->id,
                'trip_request_id' => $trip->id,
                'amount' => $offer->amount,
                'msg' => $trip->description,
                'pick_loc' => $trip->pick_loc,
                'target_loc' => $trip->target_loc,
                'status' => 'in_progress',
            ]);

            $trip->update([
                'status' => 'in_progress',
                'accepted_offer_id' => $offer->id,
                'ride_book_id' => $booking->id,
            ]);

            return $booking;
        });

        $trip = $trip->fresh(['offers.driver', 'acceptedOffer.driver']);

        $acceptedDriver = User::find($offer->driver_id);
        if ($acceptedDriver) {
            app(FcmService::class)->sendToUser(
                $acceptedDriver,
                'تم قبول عرضك',
                'قبل العميل عرضك على الطلب رقم ' . $trip->id,
                'trip_accepted',
                (string) $trip->id,
            );
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'تم قبول العرض وبدء تنفيذ الطلب',
            'data' => [
                'trip' => $this->formatTripRequestForCustomer($trip),
                'booking_id' => $booking->id,
            ],
        ]);
    }

    public function cancel(int $id)
    {
        $user = Auth::guard('api')->user();
        $trip = TripRequest::with(['offers', 'acceptedOffer'])->findOrFail($id);

        if ((int) $trip->user_id !== (int) $user->id) {
            return response()->json(['status_code' => 403, 'message' => 'غير مسموح'], 403);
        }

        if (!in_array($trip->status, ['awaiting_offers', 'in_progress'], true)) {
            return response()->json([
                'status_code' => 422,
                'message' => $trip->status === 'handed_to_driver'
                    ? 'لا يمكن إنهاء الطلب بعد تسليمه للسائق'
                    : 'لا يمكن إلغاء هذا الطلب في حالته الحالية',
            ], 422);
        }

        DB::transaction(function () use ($trip) {
            $trip->offers()->where('status', 'pending')->update(['status' => 'rejected']);
            $trip->update(['status' => 'cancelled']);

            if ($trip->ride_book_id) {
                RideBook::where('id', $trip->ride_book_id)->update(['status' => 'cancelled']);
            }
        });

        $acceptedOffer = $trip->acceptedOffer;
        if ($acceptedOffer && $acceptedOffer->driver_id) {
            $driver = User::find($acceptedOffer->driver_id);
            if ($driver) {
                app(FcmService::class)->sendToUser(
                    $driver,
                    'تم إلغاء الطلب',
                    'ألغى العميل الطلب رقم ' . $trip->id,
                    'trip_cancelled',
                    (string) $trip->id,
                );
            }
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'تم إلغاء الطلب بنجاح',
            'data' => $this->formatTripRequestForCustomer($trip->fresh(['offers.driver', 'acceptedOffer.driver'])),
        ]);
    }

    private function calculateDistanceKm($pickLat, $pickLng, $targetLat, $targetLng): ?float
    {
        if ($pickLat === null || $pickLng === null || $targetLat === null || $targetLng === null) {
            return null;
        }

        $earthRadius = 6371;
        $latFrom = deg2rad((float) $pickLat);
        $lonFrom = deg2rad((float) $pickLng);
        $latTo = deg2rad((float) $targetLat);
        $lonTo = deg2rad((float) $targetLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return round($angle * $earthRadius, 2);
    }

    private function normalizeVehicleType(?string $vehicleType): string
    {
        $vehicleType = strtolower((string) $vehicleType);

        return match ($vehicleType) {
            'toktok' => 'tricycle',
            default => $vehicleType,
        };
    }

    private function formatTripRequestForDriver(TripRequest $trip): array
    {
        return [
            'id' => $trip->id,
            'service_type' => $trip->service_type,
            'vehicle_type' => $trip->vehicle_type,
            'vehicle_type_label' => $this->vehicleTypeLabel($trip->vehicle_type),
            'pick_loc' => $trip->pick_loc,
            'target_loc' => $trip->target_loc,
            'pick_lat' => $trip->pick_lat !== null ? (float) $trip->pick_lat : null,
            'pick_lng' => $trip->pick_lng !== null ? (float) $trip->pick_lng : null,
            'target_lat' => $trip->target_lat !== null ? (float) $trip->target_lat : null,
            'target_lng' => $trip->target_lng !== null ? (float) $trip->target_lng : null,
            'distance_km' => $trip->distance_km,
            'distance_label' => $trip->distance_km ? $trip->distance_km . ' كم' : null,
            'description' => $trip->description,
            'notes' => $trip->notes,
            'status' => $trip->status,
            'created_at' => optional($trip->created_at)->format('Y-m-d H:i'),
            'can_contact' => false,
            'can_call' => false,
            'can_chat' => false,
        ];
    }

    private function formatTripRequestForDriverAfterAccept(TripRequest $trip, User $driver): array
    {
        $base = $this->formatTripRequestForDriver($trip);
        $accepted = $trip->acceptedOffer;
        $showContact = $trip->status === 'in_progress'
            && $accepted
            && (int) $accepted->driver_id === (int) $driver->id;

        if ($showContact) {
            $base['customer'] = [
                'id' => $trip->user->id,
                'name' => $trip->user->name,
                'phone' => $trip->user->phone,
            ];
            $base['can_contact'] = true;
            $base['can_call'] = true;
            $base['can_chat'] = true;
        }

        return $base;
    }

    private function formatTripRequestForCustomer(TripRequest $trip): array
    {
        $showDriver = in_array($trip->status, ['in_progress', 'handed_to_driver', 'completed'], true);
        $acceptedOffer = $trip->acceptedOffer;

        return [
            'id' => $trip->id,
            'service_type' => $trip->service_type,
            'vehicle_type' => $trip->vehicle_type,
            'vehicle_type_label' => $this->vehicleTypeLabel($trip->vehicle_type),
            'pick_loc' => $trip->pick_loc,
            'target_loc' => $trip->target_loc,
            'pick_lat' => $trip->pick_lat !== null ? (float) $trip->pick_lat : null,
            'pick_lng' => $trip->pick_lng !== null ? (float) $trip->pick_lng : null,
            'target_lat' => $trip->target_lat !== null ? (float) $trip->target_lat : null,
            'target_lng' => $trip->target_lng !== null ? (float) $trip->target_lng : null,
            'distance_km' => $trip->distance_km,
            'description' => $trip->description,
            'contact_phone' => $trip->contact_phone,
            'notes' => $trip->notes,
            'status' => $trip->status,
            'offers_count' => $trip->relationLoaded('offers')
                ? $trip->offers->where('status', 'pending')->count()
                : $trip->offers()->where('status', 'pending')->count(),
            'created_at' => optional($trip->created_at)->format('Y-m-d H:i'),
            // Chat only — never expose driver phone to customer.
            'accepted_driver' => ($showDriver && $acceptedOffer && $acceptedOffer->driver)
                ? $this->formatDriverPublic($acceptedOffer->driver, false)
                : null,
            'ride_book_id' => $trip->ride_book_id,
            'final_price' => $acceptedOffer ? (float) $acceptedOffer->amount : null,
            'can_chat' => $showDriver && !empty($trip->ride_book_id),
            'can_call' => false,
        ];
    }

    private function formatOfferForCustomer(TripOffer $offer, bool $showContact): array
    {
        return [
            'id' => $offer->id,
            'amount' => $offer->amount,
            'status' => $offer->status,
            'driver' => $this->formatDriverPublic($offer->driver, $showContact),
            'vehicle_type_label' => $this->vehicleTypeLabel($offer->driver?->vehicle_type),
            'created_at' => optional($offer->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function formatDriverPublic(?User $driver, bool $showContact): ?array
    {
        if (!$driver) {
            return null;
        }

        $data = [
            'id' => $driver->id,
            'name' => $driver->name,
            'image' => $driver->image,
            'rating' => round((float) ($driver->avg_rating ?? 0), 1),
            'avg_rating' => round((float) ($driver->avg_rating ?? 0), 1),
            'ratings_count' => (int) ($driver->ratings_count ?? 0),
            'vehicle_type' => $driver->vehicle_type,
            'vehicle_type_label' => $this->vehicleTypeLabel($driver->vehicle_type),
        ];

        if ($showContact) {
            $data['phone'] = $driver->phone;
            $data['can_call'] = true;
            $data['can_chat'] = true;
        } else {
            $data['can_call'] = false;
            $data['can_chat'] = false;
        }

        return $data;
    }

    private function vehicleTypeLabel(?string $vehicleType): string
    {
        $key = $this->normalizeVehicleType($vehicleType);
        try {
            $type = \App\Models\VehicleType::query()->where('key', $key)->first();
            if ($type) {
                return $type->displayName();
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return match ($key) {
            'motorcycle' => 'موتوسيكل',
            'scooter' => 'سكوتر',
            'tricycle' => 'تروسيكل',
            'quarter_truck' => 'ربع نقل',
            'half_truck' => 'نصف نقل',
            default => (string) $vehicleType,
        };
    }

    private function notifyDriversAboutNewTrip(TripRequest $trip): void
    {
        $proximity = app(DriverProximityService::class);
        $drivers = $proximity->nearbyOnlineDrivers(
            $trip->vehicle_type,
            $trip->pick_lat !== null ? (float) $trip->pick_lat : null,
            $trip->pick_lng !== null ? (float) $trip->pick_lng : null,
            80,
            true
        );

        $fcm = app(FcmService::class);
        $serviceLabel = $trip->service_type === 'quick_trip' ? 'مشوار سريع' : 'نقل بضائع';

        foreach ($drivers as $driver) {
            $fcm->sendToUser(
                $driver,
                'طلب جديد قريب منك',
                $serviceLabel . ' من ' . $trip->pick_loc,
                'new_trip',
                (string) $trip->id,
            );
        }
    }
}
