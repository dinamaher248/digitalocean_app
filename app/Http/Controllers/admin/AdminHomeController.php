<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\FcmService;
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
use Illuminate\Support\Facades\Schema;
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
            $user = Auth::guard('web')->user();
            if (!(bool) $user->is_admin) {
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => 'هذا الحساب غير مصرح له بدخول لوحة الإدارة.'])->onlyInput('email');
            }
            if ((bool) ($user->is_suspended ?? false)) {
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => 'تم إيقاف حساب المشرف مؤقتاً.'])->onlyInput('email');
            }
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }


        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function allUsers(Request $request)
    {
        $filter = $request->get('type', 'all');
        $segment = strtolower(trim((string) $request->get('segment', 'current')));
        if (!in_array($segment, ['new', 'current'], true)) {
            $segment = 'current';
        }
        $search = trim((string) $request->get('search', ''));

        $query = User::query()->where(function ($q) {
            $q->where('is_admin', 0)->orWhereNull('is_admin');
        });

        if ($filter === 'drivers') {
            $query->where('title', 'driver');
            if ($segment === 'new') {
                $query->where(function ($q) {
                    $q->where('is_approved', 0)->orWhereNull('is_approved');
                });
            } else {
                $query->where('is_approved', 1);
            }
        } elseif (in_array($filter, ['users', 'customers'], true)) {
            $query->where('title', 'user');
        } elseif ($filter === 'merchants') {
            $query->whereIn('title', ['merchant', 'vendor']);
        }

        if ($search !== '') {
            $query->adminSearch($search);
        }

        $users = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $stats = [
            'all' => User::where(function ($q) {
                $q->where('is_admin', 0)->orWhereNull('is_admin');
            })->count(),
            'drivers' => User::where('title', 'driver')->count(),
            'drivers_current' => User::where('title', 'driver')->where('is_approved', 1)->count(),
            'drivers_new' => User::where('title', 'driver')->where(function ($q) {
                $q->where('is_approved', 0)->orWhereNull('is_approved');
            })->count(),
            'users' => User::where('title', 'user')->count(),
            'merchants' => User::whereIn('title', ['merchant', 'vendor'])->count(),
        ];

        return view('admin.sections.users.users', compact('users', 'filter', 'stats', 'segment', 'search'));
    }

    public function storeCase(Request $request)
    {
        return back()->with('error', 'حفظ الحالة غير مفعّل في هذه النسخة.');
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
        $users = User::where('is_admin', 0)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'phone', 'title']);

        $fcm = app(FcmService::class);

        return view('admin.sections.settings.push_notify', [
            'topic' => $fcm->topic(),
            'users' => $users,
            'fcmReady' => $fcm->credentialsPath() !== null,
            'fcmExpectedPath' => storage_path(
                (string) config('services.firebase.credentials_storage')
            ),
            'debtorsCount' => $this->debtorsQuery('all', 0.01)->count(),
        ]);
    }

    /** عمود المديونية المتاح في جدول المستخدمين. */
    private function duesColumn(): ?string
    {
        foreach (['dues_balance', 'due_amount'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                return $column;
            }
        }

        return null;
    }

    /** المستخدمون الذين عليهم مديونية أكبر من حد معيّن. */
    private function debtorsQuery(string $scope, float $minAmount)
    {
        $column = $this->duesColumn();
        if ($column === null) {
            return User::query()->whereRaw('1 = 0');
        }

        $titles = match ($scope) {
            'drivers' => ['driver'],
            'merchants' => ['merchant', 'vendor'],
            default => ['driver', 'merchant', 'vendor'],
        };

        return User::query()
            ->whereIn('title', $titles)
            ->where($column, '>=', $minAmount)
            ->orderByDesc($column);
    }

    public function sendNotificationToTopicPost(Request $request, FcmService $fcmService)
    {
        $request->validate([
            'head' => 'nullable|string|max:255',
            'desc' => 'nullable|string|max:2000',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:2000',
            'action' => 'nullable|string|max:50',
            'url' => 'nullable|string|max:500',
            'audience' => 'required|in:all,customers,drivers,merchants,user,debtors',
            'user_id' => 'nullable|exists:users,id',
            'debtors_scope' => 'nullable|in:all,drivers,merchants',
            'debtors_min_amount' => 'nullable|numeric|min:0',
        ]);

        $title = trim((string) ($request->input('head') ?: $request->input('title')));
        $body = trim((string) ($request->input('desc') ?: $request->input('body')));
        $action = trim((string) $request->input('action', 'general')) ?: 'general';
        $url = trim((string) $request->input('url', ''));
        $audience = $request->input('audience', 'all');

        if ($title === '' || $body === '') {
            return redirect()
                ->route('admin.send_notification_by_topic_get')
                ->with('error', 'عنوان الإشعار ونص الإشعار مطلوبان');
        }

        if ($audience === 'user') {
            $user = User::findOrFail($request->input('user_id'));
            $result = $fcmService->sendToUser($user, $title, $body, $action, $url ?: null, $url ?: null);
            $message = $result['success']
                ? 'تم إرسال الإشعار للمستخدم المحدد'
                : ($result['error'] ?? 'فشل إرسال الإشعار');

            return redirect()->route('admin.send_notification_by_topic_get')
                ->with($result['success'] ? 'success' : 'error', $message);
        }

        if ($audience === 'debtors') {
            $column = $this->duesColumn();
            if ($column === null) {
                return redirect()->route('admin.send_notification_by_topic_get')
                    ->with('error', 'لا يوجد عمود مديونيات في جدول المستخدمين');
            }

            $minAmount = max(0.01, (float) $request->input('debtors_min_amount', 0));
            $targets = $this->debtorsQuery($request->input('debtors_scope', 'all'), $minAmount)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->get();

            if ($targets->isEmpty()) {
                return redirect()->route('admin.send_notification_by_topic_get')
                    ->with('error', 'لا يوجد مستخدمون عليهم مديونية بهذا الحد لديهم إشعارات مفعّلة');
            }

            $sent = 0;
            foreach ($targets as $user) {
                // {amount} و {name} تُستبدل بقيمة كل مستخدم على حدة.
                $amount = number_format((float) $user->{$column}, 2);
                $replacements = ['{amount}' => $amount, '{name}' => (string) $user->name];
                $personalTitle = strtr($title, $replacements);
                $personalBody = strtr($body, $replacements);

                $res = $fcmService->sendToUser(
                    $user,
                    $personalTitle,
                    $personalBody,
                    $action,
                    $url ?: null,
                    $url ?: null
                );
                if ($res['success'] ?? false) {
                    $sent++;
                }
            }

            return redirect()->route('admin.send_notification_by_topic_get')
                ->with('success', "تم إرسال الإشعار إلى {$sent} من أصحاب المديونيات");
        }

        if ($audience !== 'all') {
            $titleFilter = match ($audience) {
                'drivers' => 'driver',
                'merchants' => 'merchant',
                default => 'user',
            };

            $targets = User::where('title', $titleFilter)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->get();

            $sent = 0;
            foreach ($targets as $user) {
                $res = $fcmService->sendToUser($user, $title, $body, $action, $url ?: null, $url ?: null);
                if ($res['success'] ?? false) {
                    $sent++;
                }
            }

            return redirect()->route('admin.send_notification_by_topic_get')
                ->with('success', "تم إرسال الإشعار إلى {$sent} مستخدم");
        }

        $result = $fcmService->sendToTopic($title, $body, null, $action, $url ?: null, $url ?: null);

        if ($result['success']) {
            return redirect()
                ->route('admin.send_notification_by_topic_get')
                ->with('success', 'تم إرسال الإشعار لجميع مستخدمي التطبيق بنجاح');
        }

        return redirect()
            ->route('admin.send_notification_by_topic_get')
            ->with('error', $result['error'] ?? 'فشل إرسال الإشعار');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
