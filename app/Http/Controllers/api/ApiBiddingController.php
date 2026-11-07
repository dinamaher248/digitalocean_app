<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BiddingOffer;
use App\Models\BiddingTrip;
use App\Models\RideBook;
use App\Models\User;
use App\Services\DriverProximityService;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ApiBiddingController extends Controller
{
    public function create(Request $request)
    {
        if (!$this->tablesReady()) {
            return $this->tablesMissingResponse();
        }

        $user = Auth::guard('api')->user();

        if ($user->title === 'driver') {
            return response()->json([
                'status' => false,
                'status_code' => 403,
                'message' => 'هذه الخدمة متاحة للعملاء فقط',
            ], 403);
        }

        $data = $request->validate([
            'vehicle_type' => [
                'required',
                'string',
                'max:50',
                Rule::exists('vehicle_types', 'key'),
            ],
            'pickup_address' => 'required|string|max:500',
            'pickup_lat' => 'nullable|numeric',
            'pickup_lng' => 'nullable|numeric',
            'dropoff_address' => 'required|string|max:500',
            'dropoff_lat' => 'nullable|numeric',
            'dropoff_lng' => 'nullable|numeric',
            'description' => 'required|string|max:2000',
            'phone' => 'required|string|max:30',
            'notes' => 'nullable|string|max:2000',
            'customer_price' => 'nullable|numeric|min:1',
        ]);

        if (!Schema::hasColumn('bidding_trips', 'customer_price')) {
            unset($data['customer_price']);
        }

        $trip = BiddingTrip::create([
            ...$data,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->notifyDriversAboutNewTrip($trip);

        return response()->json([
            'status' => true,
            'status_code' => 201,
            'message' => 'تم إنشاء الطلب بنجاح',
            'data' => $this->formatTrip($trip->fresh()),
        ], 201);
    }

    public function myTrips()
    {
        if (!$this->tablesReady()) {
            return $this->tablesMissingResponse();
        }

        $user = Auth::guard('api')->user();

        $trips = BiddingTrip::query()
            ->where('user_id', $user->id)
            ->with(['acceptedOffer.driver', 'driver'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'data' => $trips->map(fn (BiddingTrip $trip) => $this->formatTrip($trip))->values(),
        ]);
    }

    public function details(int $id)
    {
        if (!$this->tablesReady()) {
            return $this->tablesMissingResponse();
        }

        $user = Auth::guard('api')->user();
        $trip = BiddingTrip::with(['acceptedOffer.driver', 'driver', 'offers.driver'])
            ->findOrFail($id);

        if ((int) $trip->user_id !== (int) $user->id && $user->title !== 'driver') {
            return response()->json([
                'status' => false,
                'status_code' => 403,
                'message' => 'غير مسموح',
            ], 403);
        }

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'data' => $this->formatTrip($trip, true),
        ]);
    }

    public function show(int $id)
    {
        return $this->details($id);
    }

    public function offers(int $id)
    {
        if (!$this->tablesReady()) {
            return $this->tablesMissingResponse();
        }

        $user = Auth::guard('api')->user();
        $trip = BiddingTrip::findOrFail($id);

        if ((int) $trip->user_id !== (int) $user->id) {
            return response()->json([
                'status' => false,
                'status_code' => 403,
                'message' => 'غير مسموح',
            ], 403);
        }

        $offers = BiddingOffer::with('driver')
            ->where('bidding_trip_id', $trip->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'data' => $offers->map(fn (BiddingOffer $offer) => $this->formatOffer($offer, false))->values(),
        ]);
    }

    public function submitOffer(Request $request, int $id)
    {
        if (!$this->tablesReady()) {
            return $this->tablesMissingResponse();
        }

        $driver = Auth::guard('api')->user();

        if ($driver->title !== 'driver') {
            return response()->json([
                'status' => false,
                'status_code' => 403,
                'message' => 'للسائقين فقط',
            ], 403);
        }

        $data = $request->validate([
            'proposed_price' => 'required_without:amount|numeric|min:1',
            'amount' => 'required_without:proposed_price|numeric|min:1',
        ]);

        $price = (float) ($data['proposed_price'] ?? $data['amount']);
        $trip = BiddingTrip::findOrFail($id);

        if ($trip->status !== 'pending') {
            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'الطلب لم يعد متاحاً للعروض',
            ], 422);
        }

        $customerPrice = (float) ($trip->customer_price ?? 0);
        $acceptsCustomerPrice = $customerPrice > 0 && abs($customerPrice - $price) < 0.009;

        $offer = BiddingOffer::updateOrCreate(
            [
                'bidding_trip_id' => $trip->id,
                'driver_id' => $driver->id,
            ],
            array_merge(BiddingOffer::priceAttributes($price), [
                'status' => $acceptsCustomerPrice ? 'accepted' : 'pending',
            ])
        );

        if ($acceptsCustomerPrice) {
            DB::transaction(function () use ($trip, $offer) {
                $offer->update(['status' => 'accepted']);
                BiddingOffer::where('bidding_trip_id', $trip->id)
                    ->where('id', '!=', $offer->id)
                    ->update(['status' => 'rejected']);

                $tripUpdate = [
                    'status' => 'in_progress',
                    'final_price' => $offer->amount(),
                ];
                if (Schema::hasColumn('bidding_trips', 'driver_id')) {
                    $tripUpdate['driver_id'] = $offer->driver_id;
                }
                if (Schema::hasColumn('bidding_trips', 'accepted_offer_id')) {
                    $tripUpdate['accepted_offer_id'] = $offer->id;
                }
                $trip->update($tripUpdate);
            });

            $customer = User::find($trip->user_id);
            if ($customer) {
                try {
                    app(FcmService::class)->sendToUser(
                        $customer,
                        'السائق قبل سعرك وبدأت الرحلة',
                        'السائق وافق على السعر وبدأ التنفيذ.',
                        'trip_accepted',
                        (string) $trip->id,
                    );
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'trip_started' => true,
                'message' => 'تم قبول سعر العميل وبدء الرحلة.',
                'data' => $this->formatOffer($offer->fresh('driver'), false),
                'trip' => $this->formatTrip($trip->fresh(), true),
            ]);
        }

        $customer = User::find($trip->user_id);
        if ($customer) {
            try {
                app(FcmService::class)->sendToUser(
                    $customer,
                    'عرض سعر جديد',
                    'قدّم السائق ' . ($driver->name ?? '') . ' عرضاً بقيمة ' . $price . ' جنيه',
                    'trip_offer',
                    (string) $trip->id,
                );
            } catch (\Throwable $e) {
                // ignore FCM failures
            }
        }

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'trip_started' => false,
            'message' => 'تم إرسال السعر الجديد للعميل. بانتظار قبوله.',
            'data' => $this->formatOffer($offer->fresh('driver'), false),
        ]);
    }

    public function acceptOffer(int $id)
    {
        if (!$this->tablesReady()) {
            return $this->tablesMissingResponse();
        }

        $user = Auth::guard('api')->user();
        BiddingTrip::ensureSchema();
        $offer = BiddingOffer::with(['trip', 'driver'])->findOrFail($id);
        $trip = $offer->trip;

        if (!$trip || (int) $trip->user_id !== (int) $user->id) {
            return response()->json([
                'status' => false,
                'status_code' => 403,
                'message' => 'غير مسموح',
            ], 403);
        }

        if ($trip->status !== 'pending') {
            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'تم قبول عرض مسبقاً أو الطلب غير متاح',
            ], 422);
        }

        if ($offer->status !== 'pending') {
            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'هذا العرض غير متاح',
            ], 422);
        }

        DB::transaction(function () use ($trip, $offer, $user) {
            $offer->update(['status' => 'accepted']);

            BiddingOffer::where('bidding_trip_id', $trip->id)
                ->where('id', '!=', $offer->id)
                ->update(['status' => 'rejected']);

            $bookingData = [
                'driver_id' => $offer->driver_id,
                'user_id' => $user->id,
                'amount' => $offer->amount(),
                'msg' => $trip->description,
                'pick_loc' => $trip->pickup_address,
                'target_loc' => $trip->dropoff_address,
                'status' => 'in_progress',
            ];
            if (Schema::hasColumn('ride_book', 'bidding_trip_id')) {
                $bookingData['bidding_trip_id'] = $trip->id;
            }
            $booking = RideBook::create($bookingData);

            $tripUpdate = [
                'status' => 'in_progress',
                'final_price' => $offer->amount(),
            ];
            if (Schema::hasColumn('bidding_trips', 'accepted_offer_id')) {
                $tripUpdate['accepted_offer_id'] = $offer->id;
            }
            if (Schema::hasColumn('bidding_trips', 'accepted_driver_id')) {
                $tripUpdate['accepted_driver_id'] = $offer->driver_id;
            }
            if (Schema::hasColumn('bidding_trips', 'driver_id')) {
                $tripUpdate['driver_id'] = $offer->driver_id;
            }
            if (Schema::hasColumn('bidding_trips', 'ride_book_id')) {
                $tripUpdate['ride_book_id'] = $booking->id;
            }
            $trip->update($tripUpdate);
        });

        $trip = $trip->fresh(['acceptedOffer.driver', 'driver']);

        if ($offer->driver) {
            try {
                app(FcmService::class)->sendToUser(
                    $offer->driver,
                    'تم قبول عرضك',
                    'قبل العميل عرضك على الطلب رقم ' . $trip->id,
                    'bidding_accepted',
                    (string) $trip->id,
                );
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'تم قبول العرض وبدء تنفيذ الطلب',
            'data' => $this->formatTrip($trip, true),
        ]);
    }

    public function cancel(int $id)
    {
        if (!$this->tablesReady()) {
            return $this->tablesMissingResponse();
        }

        $user = Auth::guard('api')->user();
        $trip = BiddingTrip::findOrFail($id);

        if ((int) $trip->user_id !== (int) $user->id) {
            return response()->json([
                'status' => false,
                'status_code' => 403,
                'message' => 'غير مسموح',
            ], 403);
        }

        if ($trip->status !== 'pending') {
            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'لا يمكن إلغاء الطلب في حالته الحالية',
            ], 422);
        }

        $trip->update(['status' => 'cancelled']);
        BiddingOffer::where('bidding_trip_id', $trip->id)
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'تم إلغاء الطلب بنجاح',
            'data' => $this->formatTrip($trip->fresh(['acceptedOffer.driver', 'driver']), true),
        ]);
    }

    private function formatTrip(BiddingTrip $trip, bool $withDriver = false): array
    {
        $payload = [
            'id' => $trip->id,
            'user_id' => $trip->user_id,
            'vehicle_type' => $trip->vehicle_type,
            'pickup_address' => $trip->pickup_address,
            'pickup_lat' => $trip->pickup_lat,
            'pickup_lng' => $trip->pickup_lng,
            'dropoff_address' => $trip->dropoff_address,
            'dropoff_lat' => $trip->dropoff_lat,
            'dropoff_lng' => $trip->dropoff_lng,
            'description' => $trip->description,
            'phone' => $trip->phone,
            'notes' => $trip->notes,
            'status' => $trip->status,
            'customer_price' => ((float) ($trip->customer_price ?? 0)) > 0
                ? (float) $trip->customer_price
                : null,
            'final_price' => $trip->final_price,
            'accepted_offer_id' => $trip->accepted_offer_id,
            'ride_book_id' => $trip->ride_book_id ?? null,
            'created_at' => optional($trip->created_at)->toIso8601String(),
        ];

        // Never expose driver phone after accept — chat only via ride_book.
        if ($trip->relationLoaded('acceptedOffer') && $trip->acceptedOffer) {
            $payload['accepted_offer'] = $this->formatOffer($trip->acceptedOffer, false);
        }

        $driver = null;
        if ($trip->relationLoaded('driver') && $trip->driver) {
            $driver = $trip->driver;
        } elseif ($trip->relationLoaded('acceptedOffer') && $trip->acceptedOffer?->driver) {
            $driver = $trip->acceptedOffer->driver;
        }

        if ($withDriver && $driver) {
            $payload['driver'] = $this->formatDriver($driver, false);
        }

        return $payload;
    }

    private function formatOffer(BiddingOffer $offer, bool $includePhone): array
    {
        $driver = $offer->relationLoaded('driver') ? $offer->driver : null;

        return [
            'id' => $offer->id,
            'trip_id' => $offer->bidding_trip_id,
            'bidding_trip_id' => $offer->bidding_trip_id,
            'driver_id' => $offer->driver_id,
            'driver_name' => $driver?->name ?? 'سائق',
            'driver_image' => $driver?->image,
            'rating' => (float) ($driver?->avg_rating ?? 0),
            'proposed_price' => $offer->amount(),
            'amount' => $offer->amount(),
            'status' => $offer->status,
            'driver_phone' => $includePhone ? ($driver?->phone) : null,
            'created_at' => optional($offer->created_at)->toIso8601String(),
            'driver' => $driver ? $this->formatDriver($driver, $includePhone) : null,
        ];
    }

    private function formatDriver(User $driver, bool $includePhone = false): array
    {
        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'image' => $driver->image,
            'phone' => $includePhone ? $driver->phone : null,
            'rating' => round((float) ($driver->avg_rating ?? 0), 1),
            'avg_rating' => round((float) ($driver->avg_rating ?? 0), 1),
            'ratings_count' => (int) ($driver->ratings_count ?? 0),
            'can_call' => $includePhone,
            'can_chat' => $includePhone,
        ];
    }

    private function notifyDriversAboutNewTrip(BiddingTrip $trip): void
    {
        try {
            $proximity = app(DriverProximityService::class);
            $drivers = $proximity->nearbyOnlineDrivers(
                $trip->vehicle_type,
                $trip->pickup_lat !== null ? (float) $trip->pickup_lat : null,
                $trip->pickup_lng !== null ? (float) $trip->pickup_lng : null,
                80,
                true
            );

            $price = (float) ($trip->customer_price ?? 0);
            $body = $price > 0
                ? 'طلب جديد مناسب لمركبتك — سعر العميل: ' . number_format($price, 0) . ' ج.م'
                : 'يوجد طلب جديد مناسب لمركبتك ضمن نطاقك';

            $fcm = app(FcmService::class);
            foreach ($drivers as $driver) {
                $fcm->sendToUser(
                    $driver,
                    'طلب مزايدة قريب منك',
                    $body,
                    'bidding_trip',
                    (string) $trip->id,
                );
            }
        } catch (\Throwable $e) {
            // ignore notification failures
        }
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('bidding_trips') && Schema::hasTable('bidding_offers');
    }

    private function tablesMissingResponse()
    {
        return response()->json([
            'status' => false,
            'status_code' => 500,
            'message' => 'جداول المزايدة غير موجودة. نفّذ: php artisan migrate',
        ], 500);
    }
}
