<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\OtpMailService;
use App\Models\RideBook;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => [
            'login',
            'register',
            'sendOtp',
            'verifyOtpAndRegister',
            'sendPasswordResetOtp',
            'resetPassword',
            'uploadPaymentProof',
            'refresh',
            'logout',
        ]]);
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

    $vehicleTypeRule = ['sometimes', 'nullable', 'string', 'max:50'];
    if (Schema::hasTable('vehicle_types')) {
        $vehicleTypeRule[] = Rule::exists('vehicle_types', 'key');
    } else {
        $vehicleTypeRule[] = Rule::in(['motorcycle', 'scooter', 'tricycle', 'toktok', 'quarter_truck', 'half_truck']);
    }

    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'username' => 'sometimes|required|string|max:255|unique:users,username,' . $user->id,
        'email' => 'sometimes|nullable|email|max:255|unique:users,email,' . $user->id,
        'phone' => 'sometimes|required|string|max:20|unique:users,phone,' . $user->id,
        'governorate' => 'sometimes|nullable|string|max:40',
        'area' => 'sometimes|nullable|string|max:255',
        'vehicle_type' => $vehicleTypeRule,
        'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        'nid_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        'password' => 'sometimes|nullable|string|min:6|confirmed',
    ]);

    if ($request->filled('vehicle_type') && $user->title !== 'driver') {
        return response()->json([
            'status' => 'error',
            'message' => 'نوع المركبة متاح للسائق فقط',
        ], 422);
    }

    if ($request->hasFile('image')) {
        if ($user->image && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        $folder = $user->title === 'driver'
            ? 'drivers/images'
            : 'users/images';

        $validated['image'] = $request->file('image')->store($folder, 'public');
    } else {
        unset($validated['image']);
    }

    if ($request->hasFile('nid_image')) {
        if ($user->title !== 'driver') {
            return response()->json([
                'status' => 'error',
                'message' => 'صورة البطاقة متاحة للسائق فقط',
            ], 422);
        }
        if ($user->nid_image && Storage::disk('public')->exists($user->nid_image)) {
            Storage::disk('public')->delete($user->nid_image);
        }
        $validated['nid_image'] = $request->file('nid_image')->store('drivers/nids', 'public');
    } else {
        unset($validated['nid_image']);
    }

    if (!empty($validated['password'])) {
        $validated['password'] = Hash::make($validated['password']);
    } else {
        unset($validated['password']);
    }
    unset($validated['password_confirmation']);

    $user->update($validated);
    $fresh = $user->fresh();

    return response()->json([
        'status' => 'success',
        'status_code' => 200,
        'message' => $fresh,
        'data' => [
            'user' => $this->formatProfileUser($fresh),
        ],
    ], 200);
}


    public function registerX(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
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
                'username' => $this->uniqueRegistrationUsername($request),
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
                'username' => $this->uniqueRegistrationUsername($request),
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


public function sendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email|max:255|unique:users',
    ]);

    $email = $this->normalizeEmail($request->email);
    $otp = (string) random_int(1000, 9999);
    Cache::put($this->otpCacheKey($email), $otp, now()->addMinutes(10));

    try {
        app(OtpMailService::class)->send($email, $otp);
    } catch (\Throwable $e) {
        Cache::forget($this->otpCacheKey($email));

        Log::error('Register OTP email failed', [
            'email' => $email,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status'  => 'error',
            'message' => 'تعذر إرسال رمز التحقق. أضف BREVO_API_KEY في إعدادات السيرفر.',
        ], 500);
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
    ], 200);
}

public function sendPasswordResetOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
    ]);

    $email = $this->normalizeEmail($request->email);
    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'لا يوجد حساب مرتبط بهذا البريد الإلكتروني',
        ], 404);
    }

    $otp = (string) random_int(1000, 9999);
    Cache::put($this->resetOtpCacheKey($email), $otp, now()->addMinutes(10));

    try {
        app(OtpMailService::class)->send($email, $otp, 'reset');
    } catch (\Throwable $e) {
        Cache::forget($this->resetOtpCacheKey($email));

        Log::error('Password reset OTP email failed', [
            'email' => $email,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status'  => 'error',
            'message' => 'تعذر إرسال رمز التحقق إلى البريد الإلكتروني',
        ], 500);
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
    ], 200);
}

public function resetPassword(Request $request)
{
    if ($request->filled('new_password') && !$request->filled('password')) {
        $request->merge([
            'password' => $request->input('new_password'),
            'password_confirmation' => $request->input('new_password_confirmation'),
        ]);
    }

    $request->validate([
        'email'    => 'required|email',
        'otp'      => 'required|string|size:4',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $email = $this->normalizeEmail($request->email);
    $cachedOtp = Cache::get($this->resetOtpCacheKey($email));

    if (!$cachedOtp || $cachedOtp !== $request->otp) {
        return response()->json([
            'status'  => 'error',
            'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
        ], 422);
    }

    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'لا يوجد حساب مرتبط بهذا البريد الإلكتروني',
        ], 404);
    }

    $user->password = Hash::make($request->input('password'));
    $user->save();

    Cache::forget($this->resetOtpCacheKey($email));

    return response()->json([
        'status'  => 'success',
        'message' => 'تم تغيير كلمة المرور بنجاح',
    ], 200);
}

public function verifyOtpAndRegister(Request $request)
{
    $rules = [
        'phone'    => 'required|string|max:20|unique:users',
        'otp'      => 'required|string|size:4',
        'name'     => 'required|string|max:255',
        'username' => 'nullable|string|max:255',
        'email'    => 'required|email|max:255|unique:users',
        'password' => 'required|string|min:6',
        'title'    => 'required|in:user,driver,merchant',
    ];

    if ($request->input('title') === 'driver') {
        $rules['vehicle_type'] = ['required', Rule::exists('vehicle_types', 'key')];
        $rules['image'] = 'required|image';
        $rules['nid_image'] = 'required|image';
    }

    $request->validate($rules, $this->registrationValidationMessages());

    $email = $this->normalizeEmail($request->email);
    $cachedOtp = Cache::get($this->otpCacheKey($email));

    if (!$cachedOtp || $cachedOtp !== $request->otp) {
        return response()->json([
            'status'  => 'error',
            'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
        ], 422);
    }

    Cache::forget($this->otpCacheKey($email));

    return $this->createRegisteredUser($request);
}

public function register(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'username' => 'nullable|string|max:255',
        'phone'    => 'required|string|max:20|unique:users',
        'email'    => 'required|email|max:255|unique:users',
        'password' => 'required|string|min:6',
        'title'    => 'required|in:user,driver,merchant',
    ], $this->registrationValidationMessages());

    return $this->createRegisteredUser($request);
}

private function createRegisteredUser(Request $request)
{
    $phone = $this->resolvePhone($request);

    if ($phone === '') {
        return response()->json([
            'status'  => 'error',
            'message' => 'رقم الهاتف مطلوب',
        ], 422);
    }

    if ($request->title === 'driver') {
        $imageFile = $request->file('image');
        $nidFile = $request->file('nid_image');
        if (!$imageFile instanceof \Illuminate\Http\UploadedFile || !$imageFile->isValid()
            || !$nidFile instanceof \Illuminate\Http\UploadedFile || !$nidFile->isValid()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'برجاء رفع صورة شخصية وصورة البطاقة القومية',
            ], 422);
        }
        $imagePath = $imageFile->store('drivers/images', 'public');
        $nidPath   = $nidFile->store('drivers/nids', 'public');

        $user = new User();
        $user->name = $request->name;
        $user->username = $this->uniqueRegistrationUsername($request);
        $user->phone = $phone;
        $user->email = trim($request->email);
        $user->password = Hash::make($request->password);
        $user->title = 'driver';
        $user->vehicle_type = $request->vehicle_type;
        $user->image = $imagePath;
        $user->nid_image = $nidPath;
        $user->governorate = $request->governorate;
        $user->area = $request->area;
        $user->is_paid = 0;
        $user->is_approved = 0;
        $this->applyLocationIdsToUser($user, $request);
        $user->save();

        $user = $user->fresh();
        $missing = $this->missingRegistrationFields($user, true);
        if (!empty($missing)) {
            $user->delete();

            return response()->json([
                'status'  => 'error',
                'message' => 'تعذر حفظ بيانات السائق: ' . implode(', ', $missing),
            ], 500);
        }

        $token = Auth::guard('api')->login($user);

        return $this->registerSuccessResponse($user, $token, 'nosub');
    }

    if ($request->title === 'merchant') {
        $user = new User();
        $user->name = $request->name;
        $user->username = $this->uniqueRegistrationUsername($request);
        $user->phone = $phone;
        $user->email = trim($request->email);
        $user->password = Hash::make($request->password);
        $user->title = 'merchant';
        $user->governorate = $request->governorate;

        if ($request->filled('area')) {
            $user->area = $request->area;
        }

        $this->applyLocationIdsToUser($user, $request);
        $user->save();

        $user = $user->fresh();
        $missing = $this->missingRegistrationFields($user, false);
        if (!empty($missing)) {
            $user->delete();

            return response()->json([
                'status'  => 'error',
                'message' => 'تعذر حفظ بيانات الحساب: ' . implode(', ', $missing),
            ], 500);
        }

        $token = Auth::guard('api')->login($user);

        return $this->registerSuccessResponse($user, $token, 'success');
    }

    $user = new User();
    $user->name = $request->name;
    $user->username = $this->uniqueRegistrationUsername($request);
    $user->phone = $phone;
    $user->email = trim($request->email);
    $user->password = Hash::make($request->password);
    $user->title = 'user';
    $user->governorate = $request->governorate;

    if ($request->filled('area')) {
        $user->area = $request->area;
    }

    $this->applyLocationIdsToUser($user, $request);
    $user->save();

    $user = $user->fresh();
    $missing = $this->missingRegistrationFields($user, false);
    if (!empty($missing)) {
        $user->delete();

        return response()->json([
            'status'  => 'error',
            'message' => 'تعذر حفظ بيانات الحساب: ' . implode(', ', $missing),
        ], 500);
    }

    $token = Auth::guard('api')->login($user);

    return $this->registerSuccessResponse($user, $token, 'success');
}

private function registrationValidationMessages(): array
{
    return [
        'title.required' => 'نوع الحساب مطلوب',
        'title.in' => 'نوع الحساب غير مدعوم. يمكنك التسجيل كعميل أو سائق أو صاحب نشاط.',
        'phone.unique' => 'رقم الهاتف مسجّل مسبقاً',
        'email.unique' => 'البريد الإلكتروني مسجّل مسبقاً',
        'username.unique' => 'اسم الحساب مقبول حتى لو متشابه، أعد المحاولة',
        'otp.size' => 'رمز التحقق يجب أن يكون 4 أرقام',
    ];
}

    private function uniqueRegistrationUsername(Request $request): string
    {
        $base = trim((string) ($request->input('username') ?: $request->input('name') ?: 'user'));
        $cleaned = preg_replace('/\s+/u', '_', $base);
        $base = (is_string($cleaned) && $cleaned !== '') ? $cleaned : 'user';
        $base = mb_substr($base, 0, 80);
        $phoneDigits = preg_replace('/\D+/', '', (string) $request->input('phone', $request->input('mobile', '')));
        $phone = is_string($phoneDigits) ? $phoneDigits : '';
        $candidate = $base;
        $i = 0;
        while (User::where('username', $candidate)->exists()) {
            $i++;
            $suffix = $phone !== '' ? substr($phone, -4) : (string) $i;
            $candidate = $base.'_'.$suffix.($i > 1 ? $i : '');
            if ($i > 40) {
                $candidate = $base.'_'.uniqid();
                break;
            }
        }

        return $candidate;
    }

private function applyLocationIdsToUser(User $user, Request $request): void
{
    if (Schema::hasColumn('users', 'governorate_id') && $request->filled('governorate_id')) {
        $user->governorate_id = $request->governorate_id;
    }

    if (Schema::hasColumn('users', 'area_id') && $request->filled('area_id')) {
        $user->area_id = $request->area_id;
    }

    if (Schema::hasColumn('users', 'location_id') && $request->filled('area_id')) {
        $user->location_id = $request->area_id;
    }
}

private function missingRegistrationFields(User $user, bool $isDriver): array
{
    $missing = [];

    if (empty($user->phone)) {
        $missing[] = 'phone';
    }
    if (empty($user->username)) {
        $missing[] = 'username';
    }
    if (empty($user->title)) {
        $missing[] = 'title';
    }

    if ($isDriver) {
        if (empty($user->vehicle_type)) {
            $missing[] = 'vehicle_type';
        }
        if (empty($user->image)) {
            $missing[] = 'image';
        }
        if (empty($user->nid_image)) {
            $missing[] = 'nid_image';
        }
    }

    return $missing;
}

private function registerSuccessResponse(User $user, string $token, string $status)
{
    return response()->json([
        'status'      => $status,
        'status_code' => 200,
        'token'       => $token,
        'user'        => $this->registrationUserPayload($user),
        'message'     => $this->registrationUserPayload($user),
    ], 200);
}

private function registrationUserPayload(User $user): array
{
    $attributes = $user->getAttributes();

    return [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'phone' => $user->phone,
        'title' => $user->title,
        'governorate' => $user->governorate,
        'area' => $attributes['area'] ?? null,
    ];
}

private function normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

private function resolvePhone(Request $request): string
{
    return trim((string) $request->input('phone', $request->input('mobile', '')));
}

private function otpCacheKey(string $email): string
{
    return 'register_otp:' . $this->normalizeEmail($email);
}

private function resetOtpCacheKey(string $email): string
{
    return 'reset_otp:' . $this->normalizeEmail($email);
}

    private function appendLocationIds(array $data, Request $request): array
    {
        if (Schema::hasColumn('users', 'governorate_id') && $request->filled('governorate_id')) {
            $data['governorate_id'] = $request->governorate_id;
        }

        if (Schema::hasColumn('users', 'area_id') && $request->filled('area_id')) {
            $data['area_id'] = $request->area_id;
        }

        if (Schema::hasColumn('users', 'location_id') && $request->filled('area_id')) {
            $data['location_id'] = $request->area_id;
        }

        return $data;
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
            'is_online' => 'required|in:0,1,true,false,"0","1"',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'current_lat' => 'nullable|numeric',
            'current_lng' => 'nullable|numeric',
        ]);

        $user = Auth::guard('api')->user();

        if ($user->title != 'driver') {
            return response()->json([
                'status' => 'error',
                'message' => 'هذا الإجراء مخصص للسائقين فقط.',
            ], 403);
        }

        $user->is_online = filter_var($request->is_online, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        $lat = $request->input('current_lat', $request->input('lat'));
        $lng = $request->input('current_lng', $request->input('lng'));
        if ($lat !== null && $lng !== null && Schema::hasColumn('users', 'current_lat')) {
            $user->current_lat = $lat;
            $user->current_lng = $lng;
            if (Schema::hasColumn('users', 'location_updated_at')) {
                $user->location_updated_at = now();
            }
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة التوفر بنجاح.',
            'is_online' => $user->is_online,
        ]);
    }

    public function updateDriverLocation(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'current_lat' => 'nullable|numeric',
            'current_lng' => 'nullable|numeric',
        ]);

        $user = Auth::guard('api')->user();

        if ($user->title !== 'driver') {
            return response()->json([
                'status' => 'error',
                'message' => 'هذا الإجراء مخصص للسائقين فقط.',
            ], 403);
        }

        if (!Schema::hasColumn('users', 'current_lat')) {
            return response()->json([
                'status' => 'error',
                'message' => 'أعمدة الموقع غير موجودة. نفّذ php artisan migrate',
            ], 500);
        }

        $user->current_lat = $data['current_lat'] ?? $data['lat'];
        $user->current_lng = $data['current_lng'] ?? $data['lng'];
        if (Schema::hasColumn('users', 'location_updated_at')) {
            $user->location_updated_at = now();
        }
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث موقعك',
            'current_lat' => (float) $user->current_lat,
            'current_lng' => (float) $user->current_lng,
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
        'email' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:40',
        'login' => 'nullable|string|max:255',
        'password' => 'required|string',
    ], [
        'password.required' => 'أدخل كلمة المرور.',
    ]);

    $identifier = trim((string) (
        $request->input('login')
        ?? $request->input('phone')
        ?? $request->input('email')
        ?? ''
    ));

    if ($identifier === '') {
        return response()->json([
            'status' => 'error',
            'message' => 'أدخل رقم الهاتف.',
        ], 422);
    }

    $userToAuth = null;
    if (str_contains($identifier, '@')) {
        $userToAuth = User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower($identifier)])->first();
    } else {
        $digits = preg_replace('/\D+/', '', $identifier) ?: $identifier;
        $tail = strlen($digits) >= 10 ? substr($digits, -10) : $digits;
        $userToAuth = User::where(function ($q) use ($identifier, $digits, $tail) {
            $q->where('phone', $identifier)
                ->orWhere('phone', $digits)
                ->orWhere('phone', 'like', '%' . $tail);
        })->first();

        if (!$userToAuth) {
            $userToAuth = User::where('username', $identifier)->first();
        }
    }

    if (!$userToAuth || !Hash::check($request->password, $userToAuth->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'رقم الهاتف أو كلمة المرور غير صحيحة.',
        ], 401);
    }

    $token = Auth::guard('api')->login($userToAuth);
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

    public function getProfile()
    {
        $user = Auth::guard('api')->user();

        $supportPhone = '01551998707';
        $privacyUrl = '';

        try {
            if (class_exists(\App\Models\Setting::class)) {
                $privacyUrl = \App\Models\Setting::getValue('privacy_policy_url', $privacyUrl);
            }
        } catch (\Throwable $e) {
            // keep defaults
        }

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'data' => [
                'user' => $this->formatProfileUser($user),
                'settings' => [
                    'support_phone' => $supportPhone,
                    'privacy_policy_url' => $privacyUrl,
                ],
            ],
        ]);
    }

    private function formatProfileUser($user): array
    {
        $vehicleType = $user->vehicle_type;
        $vehicleTypeLabel = null;

        if (!empty($vehicleType) && Schema::hasTable('vehicle_types')) {
            $row = VehicleType::query()->where('key', $vehicleType)->first();
            $vehicleTypeLabel = $row?->name_ar ?? $row?->name ?? $vehicleType;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'image' => $user->image,
            'email' => $user->email,
            'title' => $user->title,
            'vehicle_type' => $vehicleType,
            'vehicle_type_label' => $vehicleTypeLabel,
            'nid_image' => $user->nid_image,
            'governorate' => $user->governorate,
            'area' => $user->area,
        ];
    }

    public function updateProfilePost(Request $request)
    {
        return $this->updateProfile($request);
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

    public function setFcmToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:512',
        ]);

        $user = Auth::guard('api')->user();
        if (!$user && $request->filled('id')) {
            $user = User::find($request->id);
        }

        if (!$user) {
            return response()->json([
                'status_code' => 404,
                'message' => 'User not found',
            ], 404);
        }

        if (!Schema::hasColumn('users', 'fcm_token')) {
            return response()->json([
                'status_code' => 200,
                'message' => 'FCM token received',
            ]);
        }

        $user->fcm_token = $request->token;
        $user->save();

        return response()->json([
            'status_code' => 200,
            'message' => 'FCM token saved successfully',
        ]);
    }
}
