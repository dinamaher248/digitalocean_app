<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\RideBook;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'uploadPaymentProof', 'refresh', 'logout']]);
    }
public function checkStatus(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['status' => 'unauthenticated'], 401);
    }

    if ($user->title === 'driver') {
        $subscription = Subscription::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$subscription) {
            return response()->json(['status' => 'expired']);
        }

        // إذا الاشتراك لم تتم الموافقة عليه بعد
        if ($subscription->status == 'pending' || $subscription->status == 'rejected') {
            return response()->json(['status' => 'not_approved']);
        }

        // إذا تم الموافقة، نتحقق من التاريخ
        if ($subscription->status == 'approved') {
            if (is_null($subscription->start_date) || is_null($subscription->end_date)) {
                // في حالة خطأ أو البيانات غير مكتملة بعد الموافقة
                return response()->json(['status' => 'expired']);
            }

            if ($subscription->end_date < now()) {
                return response()->json(['status' => 'expired']);
            }

            return response()->json(['status' => 'success', 'user' => $user]);
        }
    }

    return response()->json(['status' => 'success', 'user' => $user]);
}

public function updateProfile(Request $request)
{
    $user = Auth::guard('api')->user();

    // validation على الفيلدز اللي بنسمح بتعديلها
    $validated = $request->validate([
        'name'        => 'sometimes|required|string|max:255',
        'username'    => 'sometimes|required|string|max:255|unique:users,username,' . $user->id,
        'email'       => 'sometimes|nullable|email|max:255|unique:users,email,' . $user->id,
        'phone'       => 'sometimes|required|string|max:20|unique:users,phone,' . $user->id,
        'governorate' => 'sometimes|nullable|string|max:40',
        'area'        => 'sometimes|nullable|string|max:255',
        'vehicle_type'=> 'sometimes|nullable|in:motorcycle,tricycle,toktok',
        // الصورة هنتحقق منها لوحدها تحت
        'image'       => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
    ]);

    // معالجة الصورة لو متبعتة
    if ($request->hasFile('image')) {
        // لو فيه صورة قديمة امسحها من الـ storage (اختياري بس أنضف)
        if ($user->image && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        // نخلي مسار الصور متناسق مع تسجيل السائق
        $folder = $user->title === 'driver'
            ? 'drivers/images'
            : 'users/images';

        $path = $request->file('image')->store($folder, 'public');
        $validated['image'] = $path;
    } else {
        // نتأكد إننا مش هنمرّر كائن UploadedFile للـ update
        unset($validated['image']);
    }

    // تحديث بيانات المستخدم
    $user->update($validated);

    return response()->json([
        'status'      => 'success',
        'status_code' => 200,
        'message'     => $user->fresh(), // يرجع البيانات بعد التحديث
    ], 200);
}


    public function registerX(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            // 'title' => 'required|in:user,driver',
            // 'email' => 'nullable|email|max:255|unique:users', 
            // 'password' => 'required|string|min:6',
        ]);

        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'Driver registered. Please upload payment proof.',
        // ]);
        if ($request->title === 'driver') {
            $request->validate([

                // 'password' => Hash::make($request->password),
                'vehicle_type' => 'required|in:motorcycle,tricycle',
                'image' => 'required|image',
                'nid_image' => 'required|image',
            ]);

            // return response()->json([
            //     'status' => 'successccc',
            //     'message' => 'Driver registered. Please upload payment proof.',
            // ]);
            $imagePath = $request->file('image')->store('drivers/images', 'public');
            $nidPath = $request->file('nid_image')->store('drivers/nids', 'public');

            $data = [
                'name' => $request->name,
                'phone' => $request->phone,
                'username' => $request->username,
                'email' => $request->email,
                'governorate' => $request->governorate,
                'request->area' => $request->area,
                'password' => Hash::make($request->password),
                'title' => 'driver',
                'vehicle_type' => $request->vehicle_type,
                'image' => $imagePath,
                'nid_image' => $nidPath,
                'is_paid' => 0,
                'is_approved' => 0,
            ];

            // أضف قيمة المنطقة فقط إذا كانت موجودة في الريكوست
            // if ($request->filled('area')) {
            //     $data['area'] = $request->request->area;
            // }

            $user = User::create($data);

            $token = Auth::guard('api')->login($user);
            return response()->json([
                'status' => 'nosub',
                'status_code' => 200,
                'message' => $user,
                'token' => $token,
            ], 200);
        } else {
            // تسجيل عميل عادي
            $data = [
                'name' => $request->name,
                'phone' => $request->phone,
                'username' => $request->username,
                'email' => $request->email,
                'governorate' => $request->governorate,
                'password' => Hash::make($request->password),
                'title' => 'user',
            ];

            // أضف area لو موجودة
            if ($request->filled('area')) {
                $data['area'] = $request->area;
            }

            $user = User::create($data);


            $token = Auth::guard('api')->login($user);

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'message' => $user,
                'token' => $token,
            ], 200);
        }
    }


public function register(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'username' => 'required|string|max:255|unique:users',
        'phone'    => 'required|string|max:20|unique:users',
        // 'email' => 'nullable|email|max:255|unique:users',
        // 'password' => 'required|string|min:6',
    ]);

    if ($request->title === 'driver') {

        $request->validate([
            'vehicle_type' => [
                'required',
                Rule::exists('vehicle_types', 'key')->where(function ($q) {
                    $q->where('is_active', 1);
                }),
            ],
            'image'     => 'required|image',
            'nid_image' => 'required|image',
        ]);

        $imagePath = $request->file('image')->store('drivers/images', 'public');
        $nidPath   = $request->file('nid_image')->store('drivers/nids', 'public');

        $data = [
            'name'        => $request->name,
            'phone'       => $request->phone,
            'username'    => $request->username,
            'email'       => $request->email,
            'governorate' => $request->governorate,
            'area'        => $request->area,      // ✅ عدلنا من 'request->area'
            'password'    => \Hash::make($request->password),
            'title'       => 'driver',
            'vehicle_type'=> $request->vehicle_type, // هنخزن الـ key زي ما هي
            'image'       => $imagePath,
            'nid_image'   => $nidPath,
            'is_paid'     => 0,
            'is_approved' => 0,
        ];

        $user  = \App\Models\User::create($data);
        $token = \Auth::guard('api')->login($user);

        return response()->json([
            'status'      => 'nosub',
            'status_code' => 200,
            'message'     => $user,
            'token'       => $token,
        ], 200);
    }

    // ---- عميل عادي ----
    $data = [
        'name'        => $request->name,
        'phone'       => $request->phone,
        'username'    => $request->username,
        'email'       => $request->email,
        'governorate' => $request->governorate,
        'password'    => \Hash::make($request->password),
        'title'       => 'user',
    ];

    if ($request->filled('area')) {
        $data['area'] = $request->area;
    }

    $user  = \App\Models\User::create($data);
    $token = \Auth::guard('api')->login($user);

    return response()->json([
        'status'      => 'success',
        'status_code' => 200,
        'message'     => $user,
        'token'       => $token,
    ], 200);
}


    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_image' => 'required|image',
        ]);

        $plan = Plan::find($request->plan_id);

        $imagePath = $request->file('payment_image')->store('subscriptions/payments', 'public');

        $subscription = Subscription::create([
            'user_id' => Auth::guard('api')->user()->id,
            'plan_id' => $plan->id,
            'payment_image' => $imagePath,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'message' => $plan,
        ], 200);
        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'Subscription request sent. Please wait for admin approval.',
        // ]);
    }



    public function uploadPaymentProof(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'payment_image' => 'required|image',
        ]);

        $user = User::find($request->user_id);

        if ($user->title !== 'driver') {
            return response()->json(['status' => 'error', 'message' => 'Only drivers need payment proof'], 400);
        }

        $paymentPath = $request->file('payment_image')->store('drivers/payments', 'public');

        $user->payment_image = $paymentPath;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment proof uploaded. Please wait for admin approval.',
        ]);
    }

    public function toggleOnlineStatus(Request $request)
    {
        $request->validate([
            'is_online' => 'required|boolean',
        ]);

        $user = Auth::guard('api')->user();

        if ($user->title != 'driver') {
            return response()->json([
                'status' => 'error',
                'message' => 'هذا الإجراء مخصص للسائقين فقط.',
            ], 403);
        }

        $user->is_online = $request->is_online;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة التوفر بنجاح.',
            'is_online' => $user->is_online,
        ]);
    }

public function getAvailableDrivers(Request $request)
{
    $today = Carbon::now()->toDateString();
    $userId = auth()->id();
    $governorate = $request->input('governorate');

    $query = User::where('title', 'driver')
        ->where('is_online', 1)
        ->whereHas('subscription', function ($q) use ($today) {
            $q->where('status', 'approved')
              ->whereDate('start_date', '<=', $today)
              ->whereDate('end_date', '>=', $today);
        });

    if ($governorate) {
        $query->where('governorate', $governorate);
    }

    $drivers = $query->get();

    $drivers->transform(function ($driver) use ($userId) {
        $driver->is_booked = RideBook::where('driver_id', $driver->id)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();
        return $driver;
    });

    return response()->json([
        'status' => 'success',
        'status_code' => 200,
        'message' => $drivers
    ], 200);
}

    public function getDrivers(Request $request)
{
    $today = Carbon::now()->toDateString();
    $userId = Auth::guard('api')->user()->id;

    $drivers = User::where('title', 'driver')
        ->where('is_online', 1) // ✅ فقط السائقين المتاحين حالياً
        ->whereHas('subscription', function ($q) use ($today) {
            $q->where('status', 'approved')
              ->whereDate('start_date', '<=', $today)
              ->whereDate('end_date', '>=', $today);
        })
        ->get();    

    // إضافة is_booked لكل سائق مع المحافظة على باقي بياناته كما هي
    $drivers->transform(function ($driver) use ($userId) {
        $driver->is_booked = \App\Models\RideBook::where('driver_id', $driver->id)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();
        return $driver;
    });

    return response()->json([
        'status' => 'success',
        'status_code' => 200,
        'message' => $drivers
    ], 200);
}


public function login(Request $request)
{
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    $credentials = $request->only('email', 'password');

    if (!$token = Auth::guard('api')->attempt($credentials)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid email or password.',
        ], 401);
    }

    $user = Auth::guard('api')->user();

    if ($user->title === 'driver') {
        $subscription = Subscription::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // No subscription at all
        if (!$subscription) {
            return response()->json([
                'status' => 'nosub',
                'token' => $token,
                'user' => $user,
            ]);
        }

        // Subscription refused or pending
        if ($subscription->status === 'rejected' || $subscription->status === 'pending') {
            return response()->json([
                'status' => 'not_approved',
                'token' => $token,
                'user' => $user,
            ]);
        }

        // Subscription approved, check dates
        if ($subscription->status === 'approved') {
            if (is_null($subscription->start_date) || is_null($subscription->end_date) || $subscription->end_date < now()) {
                return response()->json([
                    'status' => 'expired',
                    'token' => $token,
                    'user' => $user,
                ]);
            }

            // Success - everything is fine
            return response()->json([
                'status' => 'success',
                'token' => $token,
                'user' => $user,
            ]);
        }
    }

    // Normal users or non-drivers
    return response()->json([
        'status' => 'success',
        'token' => $token,
        'user' => $user,
    ]);
}


    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }


    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::guard('api')->user(),
            'authorisation' => [
                'token' => Auth::guard('api')->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
    public function me()
    {
        return response()->json([
            'status_code' => 200,
            'message' => Auth::guard('api')->user()
        ]);
    }
    public function meX()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::guard('api')->user(),
        ]);
    }
    public function update(Request $request)
    {
        $user = Auth::guard('api')->user();
        $user->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }
    public function delete()
    {
        $user = Auth::guard('api')->user();
        $user->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully',
        ]);
    }
    public function updatePassword(Request $request)
    {
        $user = Auth::guard('api')->user();
        $request->validate([
            'password' => 'required|string|min:6',
        ]);
        $user->update([
            'password' => Hash::make($request->password),
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully',
        ]);
    }
    public function updateEmail(Request $request)
    {
        $user = Auth::guard('api')->user();
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
        ]);
        $user->update([
            'email' => $request->email,
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Email updated successfully',
        ]);
    }
    public function updateName(Request $request)
    {
        $user = Auth::guard('api')->user();
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $user->update([
            'name' => $request->name,
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Name updated successfully',
        ]);
    }
    public function updateProfilePicture(Request $request)
    {
        $user = Auth::guard('api')->user();
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        $imageName = time() . '.' . $request->profile_picture->extension();
        $request->profile_picture->move(public_path('images'), $imageName);
        $user->update([
            'profile_picture' => $imageName,
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Profile picture updated successfully',
        ]);
    }
}
