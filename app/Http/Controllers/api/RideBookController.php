<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Car;
use App\Models\Category;
use App\Models\File;
use App\Models\Image;
use App\Models\PatientCase;
use App\Models\Ride;
use App\Models\RideBook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RideBookController extends Controller
{

    // List all upcoming rides (public)
    public function index()
    {
        $rides = Ride::with('car', 'user')
            ->where('departure_time', '>', now())
            ->orderBy('departure_time')
            ->get();

        return response()->json($rides);
    }
    public function store(Request $request)
    {
        // $request->validate([
        //     // 'ride_id' => 'required|exists:ride_book,id',
        //     'seat_count' => 'required|integer|min:1',
        // ]);
    
        $user = Auth::guard('api')->user();
        $driver = User::findOrFail($request->driver_id);
    
        // Check if enough seats are available
        // $bookedSeats = RideBook::where('driver_id', $driver->id)
        //     ->where('status', 'approved') // or any status you use for confirmed bookings
        //     ->sum('seat_count');
    
        // Create booking
        $booking = RideBook::create([
            'driver_id' => $driver->id,
            'user_id' => $user->id,
            // 'seat_count' => $request->seat_count,
            'amount' => $request->amount,
            'msg' => $request->msg,
            'pick_loc' => $request->pick_loc,
            'target_loc' => $request->target_loc,
            'status' => 'pending',
        ]);
    
        return response()->json([
          'status_code' => 200,
          'message' => $booking
        ], 200);
    }

public function getOrdersData(Request $request)
{
    $user = Auth::guard('api')->user();

    if (!$user) {
        return response()->json([
            'status_code' => 401,
            'status'      => 'error',
            'message'     => 'Unauthenticated',
        ], 401);
    }

    // فلتر اختياري للحالة: accepted / refused / pending / all
    $allowedStatuses = ['accepted', 'refused', 'pending', 'completed', 'all'];
    $statusFilter    = $request->query('status');

    if ($statusFilter && !in_array($statusFilter, $allowedStatuses)) {
        $statusFilter = null; // تجاهل أي قيمة غلط
    }

    // ========= تحديد الكويري الأساسي حسب نوع المستخدم =========
    $ordersQuery = RideBook::query();

    if ($user->title === 'driver') {
        // السائق → أنا الـ driver_id
        $ordersQuery
            ->where('driver_id', $user->id)
            ->with('user'); // العميل
    } else {
        // user عادي → أنا الـ user_id
        $ordersQuery
            ->where('user_id', $user->id)
            ->with('driver'); // السائق
    }

    // تطبيق الفلتر لو مبعوت
    if ($statusFilter && $statusFilter !== 'all') {
        $ordersQuery->where('status', $statusFilter);
    }

    // ترتيب من الأحدث للأقدم
    $ordersCollection = $ordersQuery
        ->orderBy('created_at', 'desc')
        ->get();

    // ========= إحصائيات عامة للـ user الحالي =========
    $statsBaseQuery = RideBook::query();
    if ($user->title === 'driver') {
        $statsBaseQuery->where('driver_id', $user->id);
    } else {
        $statsBaseQuery->where('user_id', $user->id);
    }

    $stats = [
        'accepted_count' => (clone $statsBaseQuery)->where('status', 'accepted')->count(),
        'refused_count'  => (clone $statsBaseQuery)->where('status', 'refused')->count(),
        'pending_count'  => (clone $statsBaseQuery)->where('status', 'pending')->count(),
        // لو عندك حالة "completed" أضفها هنا
        // 'completed_count' => (clone $statsBaseQuery)->where('status', 'completed')->count(),
    ];

    // ========= فورمات موّحد للطلبات =========
    $orders = $ordersCollection->map(function (RideBook $order) use ($user) {
        // لو أنا سائق → other_party = user (العميل)
        // لو أنا user → other_party = driver (السائق)
        $other = $user->title === 'driver'
            ? $order->user
            : $order->driver;

        return [
            'id'         => $order->id,
            'amount'     => $order->amount,
            'seat_count' => $order->seat_count,
            'msg'        => $order->msg,
            'pick_loc'   => $order->pick_loc,
            'target_loc' => $order->target_loc,
            'status'     => $order->status,
            'created_at' => optional($order->created_at)->format('Y-m-d H:i'),

            'other_party' => [
                'id'           => $other->id           ?? null,
                'name'         => $other->name         ?? null,
                'phone'        => $other->phone        ?? null,
                'title'        => $other->title        ?? null,
                'vehicle_type' => $other->vehicle_type ?? null,
                'governorate'  => $other->governorate  ?? null,
                'area'         => $other->area         ?? null,
            ],
        ];
    })->values(); // values() عشان نرجع أرقام مصفوفة من 0..n

    return response()->json([
        'status_code' => 200,
        'status'      => 'success',
        'role'        => $user->title,
        'stats'       => $stats,
        'orders'      => $orders,
    ], 200);
}



public function getHistory(Request $request)
{
    $user = Auth::guard('api')->user();

    if (!$user) {
        return response()->json([
            'status_code' => 401,
            'status'      => 'error',
            'message'     => 'Unauthenticated',
        ], 401);
    }

    // فلتر الحالة
    $allowedStatuses = ['accepted', 'refused', 'pending', 'completed', 'all'];
    $statusFilter    = $request->query('status', 'all');

    if (!in_array($statusFilter, $allowedStatuses)) {
        $statusFilter = 'all';
    }

    // فلترة بالتاريخ (اختياري) بصيغة Y-m-d
    $fromDate = $request->query('from_date'); // مثلاً 2025-01-01
    $toDate   = $request->query('to_date');   // مثلاً 2025-12-31

    $historyQuery = RideBook::query();

    if ($user->title === 'driver') {
        // السائق → driver_id
        $historyQuery
            ->where('driver_id', $user->id)
            ->with('user'); // العميل
    } else {
        // المستخدم → user_id
        $historyQuery
            ->where('user_id', $user->id)
            ->with('driver'); // السائق
    }

    // فلتر الحالة
    if ($statusFilter !== 'all') {
        $historyQuery->where('status', $statusFilter);
    }

    // فلتر التاريخ (لو مبعوت)
    if (!empty($fromDate)) {
        $historyQuery->whereDate('created_at', '>=', $fromDate);
    }
    if (!empty($toDate)) {
        $historyQuery->whereDate('created_at', '<=', $toDate);
    }

    $historyCollection = $historyQuery
        ->orderBy('created_at', 'desc')
        ->get();

    // إحصائيات للهستوري المعروض (بعد تطبيق الفلاتر)
    $stats = [
        'total_count'    => $historyCollection->count(),
        'accepted_count' => $historyCollection->where('status', 'accepted')->count(),
        'refused_count'  => $historyCollection->where('status', 'refused')->count(),
        'pending_count'  => $historyCollection->where('status', 'pending')->count(),
        'completed_count'=> $historyCollection->where('status', 'completed')->count(),
    ];

    // فورمات موحّد للريسبونس
    $history = $historyCollection->map(function (RideBook $order) use ($user) {
        $other = $user->title === 'driver'
            ? $order->user   // السائق يشوف بيانات العميل
            : $order->driver; // العميل يشوف بيانات السائق

        return [
            'id'         => $order->id,
            'amount'     => $order->amount,
            'seat_count' => $order->seat_count,
            'msg'        => $order->msg,
            'pick_loc'   => $order->pick_loc,
            'target_loc' => $order->target_loc,
            'status'     => $order->status,
            'created_at' => optional($order->created_at)->format('Y-m-d H:i'),

            'other_party' => [
                'id'           => $other->id           ?? null,
                'name'         => $other->name         ?? null,
                'phone'        => $other->phone        ?? null,
                'title'        => $other->title        ?? null,
                'vehicle_type' => $other->vehicle_type ?? null,
                'governorate'  => $other->governorate  ?? null,
                'area'         => $other->area         ?? null,
            ],
        ];
    })->values();

    return response()->json([
        'status_code' => 200,
        'status'      => 'success',
        'role'        => $user->title,
        'filters'     => [
            'status'    => $statusFilter,
            'from_date' => $fromDate,
            'to_date'   => $toDate,
        ],
        'stats'       => $stats,
        'history'     => $history,
    ], 200);
}



    public function getBookingRequests(Request $request)
{
    $driverId = auth()->id(); // تأكد أن السائق مسجل دخوله

    $bookings = RideBook::with('user') // Assuming 'user' هي العلاقة مع جدول المستخدمين
        ->where('driver_id', $driverId)
        ->whereIn('status', ['pending', 'accepted'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($booking) {
            return [
                'id' => $booking->id,
                'customer_name' => $booking->user->name ?? 'N/A',
                'customer_phone' => $booking->user->phone ?? 'N/A',
                'seat_count' => $booking->seat_count,
                'amount' => $booking->amount,
                'msg' => $booking->msg,
                'pick_loc' => $booking->pick_loc,
                'target_loc' => $booking->target_loc,
                'status' => $booking->status,
                'created_at' => $booking->created_at->format('Y-m-d H:i'),
            ];
        });

    return response()->json([
        'status_code' => 200,
        'message' => $bookings,
    ], 200);
}

public function updateBookingStatus(Request $request)
{
    $request->validate([
        'id' => 'required|exists:ride_book,id',
        'status' => 'required|in:pending,canceled,refused,accepted,finished',
    ]);

    $booking = RideBook::find($request->id);

    // تحقق أن المستخدم الذي يعدل هو صاحب الحجز (السائق نفسه)
    if ($booking->driver_id != auth()->id()) {
        return response()->json([
            'status' => 'error',
            'message' => 'غير مصرح لك بتنفيذ هذا الطلب.',
        ], 403);
    }

    $booking->status = $request->status;
    $booking->save();

    return response()->json([
        'status' => 'success',
        'message' => 'تم تحديث حالة الحجز بنجاح.',
    ]);
}


    public function rideFinished(Request $request)
    {
       
        User::where('id', Auth::guard('api')->user()->id)->increment('coins', 500);
    
        return response()->json([
          'status_code' => 200,
          'message' => 'ok'
        ], 200);
    }
    // Create new ride
  

    // Show specific ride
    public function show($id)
    {
        $ride = Ride::with('car', 'user')->findOrFail($id);
        return response()->json($ride);
    }

    // List user-created rides
    public function userRides()
    {
        $rides = Ride::with('car')->where('user_id', Auth::id())->get();
        return response()->json($rides);
    }

    // Delete a ride
    public function destroy($id)
    {
        $ride = Ride::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $ride->delete();

        return response()->json(['message' => 'Ride deleted successfully']);
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
}
