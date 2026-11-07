<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\KoshyApp;
use App\Models\PatientCase;
use App\Models\RideBook;
use App\Models\Setting;
use App\Models\User;
use App\Services\WelcomeScreenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ApiHomeController extends Controller
{

     public function index(Request $request)
    {
        $user  = Auth::guard('api')->user();
        $today = Carbon::now()->toDateString();

        // -------- 1) profile مشترك للطرفين --------
        $profile = [
            'id'           => $user->id,
            'name'         => $user->name,
            'username'     => $user->username,
            'phone'        => $user->phone,
            'email'        => $user->email,
            'title'        => $user->title,      // user / driver
            'governorate'  => $user->governorate,
            'area'         => $user->area,
            'vehicle_type' => $user->vehicle_type,
            'coins'        => $user->coins,
            'is_online'    => (bool)$user->is_online,
        ];

        // ---------------- DRIVER ----------------
        if ($user->title === 'driver') {

            // 2) حالة الاشتراك
            $subscriptionStatus = 'none';
            $activeSubscription = null;

            $lastSub = $user->subscription()
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastSub) {
                if ($lastSub->status === 'pending') {
                    $subscriptionStatus = 'pending';
                } elseif ($lastSub->status === 'rejected') {
                    $subscriptionStatus = 'rejected';
                } elseif (
                    $lastSub->status === 'approved' &&
                    $lastSub->start_date <= $today &&
                    $lastSub->end_date   >= $today
                ) {
                    $subscriptionStatus = 'active';
                    $activeSubscription = [
                        'plan_id'   => $lastSub->plan_id,
                        'plan_name' => optional($lastSub->plan)->name,
                        'start_date'=> $lastSub->start_date,
                        'end_date'  => $lastSub->end_date,
                    ];
                } elseif ($lastSub->end_date < $today) {
                    $subscriptionStatus = 'expired';
                } else {
                    $subscriptionStatus = strtolower($lastSub->status);
                }
            }

            $profile['subscription_status'] = $subscriptionStatus;
            $profile['active_subscription'] = $activeSubscription;

            // 3) الطلبات الجديدة (pending + accepted) = اللي كنت بتجيبها في getBookingRequests
            $bookingRequests = RideBook::with('user')
                ->where('driver_id', $user->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($booking) {
                    return [
                        'id'             => $booking->id,
                        'customer_name'  => $booking->user->name ?? 'N/A',
                        'customer_phone' => $booking->user->phone ?? 'N/A',
                        'seat_count'     => $booking->seat_count,
                        'amount'         => $booking->amount,
                        'msg'            => $booking->msg,
                        'pick_loc'       => $booking->pick_loc,
                        'target_loc'     => $booking->target_loc,
                        'status'         => $booking->status,
                        'created_at'     => $booking->created_at->format('Y-m-d H:i'),
                    ];
                })->values();

            // 4) history/الطلبات كلها + الإحصائيات = اللي كنت بتجيبه من getOrdersData للسائق
            $acceptedCount = RideBook::where('driver_id', $user->id)
                ->where('status', 'accepted')
                ->count();

            $refusedCount = RideBook::where('driver_id', $user->id)
                ->where('status', 'refused')
                ->count();

            $orders = RideBook::with('user')
                ->where('driver_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'id'             => $order->id,
                        'customer_name'  => $order->user->name ?? '',
                        'customer_phone' => $order->user->phone ?? '',
                        'seat_count'     => $order->seat_count,
                        'amount'         => $order->amount,
                        'msg'            => $order->msg,
                        'pick_loc'       => $order->pick_loc,
                        'target_loc'     => $order->target_loc,
                        'status'         => $order->status,
                        'created_at'     => $order->created_at->format('Y-m-d H:i'),
                    ];
                })->values();

            return response()->json([
                'status_code' => 200,
                'role'        => 'driver',
                'profile'     => $profile,
                'data'        => [
                    'booking_requests' => $bookingRequests,
                    'orders'           => $orders,
                    'stats'            => [
                        'acceptedCount' => $acceptedCount,
                        'refusedCount'  => $refusedCount,
                    ],
                ],
            ], 200);
        }

        // ---------------- USER (client) ----------------

        // 2) السائقين المتاحين = المنطق بتاع getDrivers
        $driversQuery = User::where('title', 'driver')
            ->where('is_online', 1)
            ->whereHas('subscription', function ($q) use ($today) {
                $q->where('status', 'approved')
                  ->whereDate('start_date', '<=', $today)
                  ->whereDate('end_date', '>=', $today);
            });

        // لو حابب تضيف فلاتر governorate/area من الـ request:
        if ($request->has('governorate')) {
            $driversQuery->where('governorate', $request->query('governorate'));
        }

        $drivers = $driversQuery->get();

        $drivers->transform(function ($driver) use ($user) {
            $driver->is_booked = RideBook::where('driver_id', $driver->id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->exists();

            return [
                'id'           => $driver->id,
                'name'         => $driver->name,
                'phone'        => $driver->phone,
                'vehicle_type' => $driver->vehicle_type,
                'governorate'  => $driver->governorate,
                'area'         => $driver->area,
                'is_online'    => (bool)$driver->is_online,
                'is_booked'    => (bool)$driver->is_booked,
            ];
        });

        // 3) طلبات العميل (history) = فرع user من getOrdersData
        $orders = RideBook::with('driver')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id'         => $order->id,
                    'amount'     => $order->amount,
                    'msg'        => $order->msg,
                    'pick_loc'   => $order->pick_loc,
                    'target_loc' => $order->target_loc,
                    'status'     => $order->status,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                    'driver'     => $order->driver ? [
                        'id'           => $order->driver->id,
                        'name'         => $order->driver->name,
                        'phone'        => $order->driver->phone,
                        'vehicle_type' => $order->driver->vehicle_type,
                    ] : null,
                ];
            })->values();

        return response()->json([
            'status_code' => 200,
            'role'        => 'user',
            'profile'     => $profile,
            'data'        => [
                'drivers' => $drivers,
                'orders'  => $orders,
            ],
        ], 200);
    }





















    
     public function getKoshyApp()
    {
        $phone = null;

        try {
            if (Schema::hasTable('settings')) {
                $phone = Setting::getValue('wallet_number');
            }
        } catch (\Throwable $e) {
            // Ignore and try fallback sources.
        }

        if (empty($phone)) {
            try {
                $phone = KoshyApp::query()->value('phone');
            } catch (\Throwable $e) {
                // Ignore and try env fallback.
            }
        }

        if (empty($phone)) {
            $phone = config('services.wallet.transfer_number');
        }

        return response()->json([
            'status_code' => 200,
            'wallet_number' => $phone,
            'message' => [
                'phone' => $phone,
                'wallet_number' => $phone,
                'value' => $phone,
            ],
        ]);
    }

    public function getWelcomeScreen()
    {
        return response()->json(WelcomeScreenService::apiPayload());
    }  

    
    public function getCases()
    {
        try {
            $cases = PatientCase::select()->with('doctor')->with('images')->get();

            return response()->json([
                'status_code' => 200,
                'message' => $cases
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    public function getCaseDetail($case_id)
    {
        try {
            $case = PatientCase::select()->with('doctor')->with('images')->where('id', $case_id)->first();

            if (!$case) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Case not found'
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'message' => $case
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

      public function withdraw(Request $request)
    {
       
        User::where('id', Auth::guard('api')->user()->id)->decrement('coins', $request->amount);
    
        return response()->json([
          'status_code' => 200,
          'message' => 'ok'
        ], 200);
    }

    public function requiredDocuments(\Illuminate\Http\Request $request)
    {
        $role = strtolower(trim((string) $request->query('role', $request->query('audience', 'driver'))));
        $vehicleType = trim((string) $request->query('vehicle_type', ''));
        $vehicleType = $vehicleType !== '' ? $vehicleType : null;
        $hubSection = strtolower(trim((string) $request->query('hub_section', $request->query('vendor_hub_section', ''))));
        $hubSection = in_array($hubSection, ['orders', 'bookings', 'services'], true) ? $hubSection : null;

        try {
            $items = \App\Models\RegistrationDocument::forRegistrationApi($role, $vehicleType, $hubSection);

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'data' => $items,
                'documents' => $items,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('requiredDocuments failed: '.$e->getMessage());
            $items = \App\Models\RegistrationDocument::fallbackRegistrationDocuments($role);

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'data' => $items,
                'documents' => $items,
            ]);
        }
    }
}
