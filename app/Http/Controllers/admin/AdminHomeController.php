<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\File;
use App\Models\Image;
use App\Models\PatientCase;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminHomeController extends Controller
{

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }


        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function allUsers(Request $request)
{
    // نوع الفلتر: all / drivers / users
    $filter = $request->get('type', 'all');

    $query = User::query();

    if ($filter === 'drivers') {
        $query->where('title', 'driver');
    } elseif ($filter === 'users') {
        $query->where('title', 'user');
    }

    $users = $query->orderBy('created_at', 'desc')->get();

    // إحصائيات سريعة
    $stats = [
        'all'     => User::count(),
        'drivers' => User::where('title', 'driver')->count(),
        'users'   => User::where('title', 'user')->count(),
    ];

    return view('admin.sections.users.users', compact('users', 'filter', 'stats'));
}

public function deleteUser($id)
{
    $user = User::where('id', $id)->firstOrFail();
    $user->delete();

    return redirect()
        ->route('admin.user.index')
        ->with('success', 'تم حذف المستخدم بنجاح');
}

    public function getRegisterationRequests()
    {
        $registerRequests = Subscription::select()->whereHas('doctor', function ($query) {
            $query->where('type', '!=', 2);
        })->with('doctor')->get();

        return view('admin.sections.doctors.register_request', compact('registerRequests'));
    }

     public function getSubscriptionRequests(Request $request)
    {
        // الحالة الحالية للفلاتر (افتراضي: pending)
        $status = $request->get('status', 'pending');

        $query = Subscription::with(['user', 'plan'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $registerRequests = $query->get();

        // إحصائيات سريعة
        $stats = [
            'pending'  => Subscription::where('status', 'pending')->count(),
            'approved' => Subscription::where('status', 'approved')->count(),
            'rejected' => Subscription::where('status', 'rejected')->count(),
        ];

        return view('admin.sections.users.register_request', compact(
            'registerRequests',
            'status',
            'stats',
        ));
    }

 public function updateStatus($id, $status)
    {
        $subscription = Subscription::with(['plan', 'user'])->findOrFail($id);

        if (! in_array($status, ['approved', 'rejected'])) {
            return back()->with('error', 'حالة غير صحيحة.');
        }

        // لو الطلب اتراجع عنه قبل كده
        if ($subscription->status !== 'pending') {
            return back()->with('error', 'تم التعامل مع هذا الطلب من قبل.');
        }

        $subscription->status = $status;

        if ($status === 'approved') {
            $plan = $subscription->plan;

            if (! $plan) {
                return back()->with('error', 'خطة الاشتراك غير موجودة.');
            }

            $subscription->start_date = now();

            // حسب ما عندك في جدول الخطط: days أو months
            if (! empty($plan->days)) {
                $subscription->end_date = now()->addDays($plan->days);
            } elseif (! empty($plan->months)) {
                $subscription->end_date = now()->addMonths($plan->months);
            }

            // تحديث بيانات السائق لو الأعمدة دي موجودة في users
            if ($subscription->user) {
                $subscription->user->update([
                    'is_paid'      => 1,
                    'is_approved'  => 1,
                    'subscription_end_date' => $subscription->end_date,
                ]);
            }
        }

        $subscription->save();

        return back()->with('success', 'تم تحديث حالة الاشتراك بنجاح.');
    }








    public function storeUser(Request $request)
    {
        $rules = [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ];
          $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'type'=> 1
        ]);
        
        return redirect()->route('admin.user.index');
    }

    public function sendNotificationByTopic()
    {
        $topic = 'all_users';
        $title = 'Test Notification';
        $body = 'This is a test notification sent to all users.';

        $message = [
            'title' => $title,
            'body' => $body,
        ];

        // Send the notification
        // $response = FCM::sendToTopic($topic, null, null, $message);
        return view('admin.sections.settings.push_notify');
        return response()->json([
            'status_code' => 200,
            'message' => 'Notification sent successfully',
            'response' => $response,
        ]);
    }
    public function sendNotificationToTopicPost(Request $request)
    {
        $projectId = 'quickmart-fbc37';

        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return response()->json(["error" => "Failed to retrieve access token"], 500);
        }

        // if($request->type == 'new_branch'){
        //     Message::create([
        //         'title' => $request->head,
        //         'body' => $request->desc,
        //         'type' => $request->type,
        //         'table_id' => 1,
        //     ]);
        // }


        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        $data = [
            "message" => [
                // "topic" => $request->topic,
                "topic" => 'motorent',
                "data" => [
                    'head' => $request->head,
                    'desc' => $request->desc,
                    'mtopic' => $request->mtopic,
                    // 'action' => $request->action,
                    'action' => 'open',
                    // 'url' => $request->url,
                    'url' => '',
                ],
            ]
        ];

        $response = Http::withHeaders([ 
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post($url, $data);

        return redirect()->route('admin.send_notification_by_topic_get');
        return print_r($accessToken);
        return view('admin.sections.settings.push_notify');
        return $response->json();
    }
    public function getAccessToken(): ?string
    {
        $keyFilePath = storage_path('app/firebase/quickmart-fbc37-firebase-adminsdk-fbsvc-e65b3f0332.json');

        if (!file_exists($keyFilePath)) {
            return null;
        }

        $googleAuthUrl = "https://oauth2.googleapis.com/token";

        $serviceAccount = json_decode(file_get_contents($keyFilePath), true);

        $jwtHeader = base64_encode(json_encode(["alg" => "RS256", "typ" => "JWT"]));

        $iat = time();
        $exp = $iat + 3600;

        $jwtPayload = base64_encode(json_encode([
            "iss" => $serviceAccount["client_email"],
            "scope" => "https://www.googleapis.com/auth/firebase.messaging",
            "aud" => $googleAuthUrl,
            "exp" => $exp,
            "iat" => $iat
        ]));

        $signature = "";
        openssl_sign("$jwtHeader.$jwtPayload", $signature, openssl_pkey_get_private($serviceAccount["private_key"]), OPENSSL_ALGO_SHA256);
        $jwtAssertion = "$jwtHeader.$jwtPayload." . base64_encode($signature);

        $response = Http::post($googleAuthUrl, [
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion" => $jwtAssertion
        ]);

        return $response->json()["access_token"] ?? null;
    }
}
