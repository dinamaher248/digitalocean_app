<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\SettlementRequest;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettlementAdminController extends Controller
{
    public function index()
    {
        $requests = SettlementRequest::with('user')
            ->orderByDesc('id')
            ->paginate(30);

        return view('admin.sections.settlements.index', compact('requests'));
    }

    public function approve(int $id)
    {
        $request = SettlementRequest::with('user')->findOrFail($id);

        if ($request->status !== 'pending') {
            return back()->with('error', 'تمت معالجة هذا الطلب مسبقاً');
        }

        DB::transaction(function () use ($request) {
            $user = $request->user;
            $newBalance = max(0, (float) $user->dues_balance - (float) $request->amount);
            $user->update(['dues_balance' => $newBalance]);
            $request->update(['status' => 'approved']);
        });

        if ($request->user) {
            app(FcmService::class)->sendToUser(
                $request->user,
                'تم تأكيد السداد',
                'تم خصم المستحقات من رصيدك بنجاح',
                'general',
            );
        }

        return back()->with('success', 'تم تأكيد السداد وخصم الرصيد');
    }

    public function reject(Request $httpRequest, int $id)
    {
        $request = SettlementRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return back()->with('error', 'تمت معالجة هذا الطلب مسبقاً');
        }

        $request->update([
            'status' => 'rejected',
            'admin_note' => $httpRequest->input('admin_note'),
        ]);

        if ($request->user) {
            app(FcmService::class)->sendToUser(
                $request->user,
                'لم يتم تأكيد السداد',
                'تعذر تأكيد طلب السداد — الرصيد كما هو',
                'general',
            );
        }

        return back()->with('success', 'تم رفض طلب السداد');
    }
}
