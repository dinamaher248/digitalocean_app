<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderByDesc('id')->get();

        return view('admin.sections.plans.index', compact('plans'));
    }

    public function create()
    {
        $plan = new Plan(); // عشان نستخدمه في الـ _form
        return view('admin.sections.plans.create', compact('plan'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:40',
            'price' => 'required|integer|min:0',
            'offer' => 'required|integer|min:0',
            'days'  => 'required|integer|min:1',
        ], [
            'name.required'  => 'اسم الخطة مطلوب',
            'price.required' => 'السعر مطلوب',
            'offer.required' => 'قيمة العرض مطلوبة',
            'days.required'  => 'عدد الأيام مطلوب',
        ]);

        Plan::create($data);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'تمت إضافة الخطة بنجاح.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.sections.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:40',
            'price' => 'required|integer|min:0',
            'offer' => 'required|integer|min:0',
            'days'  => 'required|integer|min:1',
        ], [
            'name.required'  => 'اسم الخطة مطلوب',
            'price.required' => 'السعر مطلوب',
            'offer.required' => 'قيمة العرض مطلوبة',
            'days.required'  => 'عدد الأيام مطلوب',
        ]);

        $plan->update($data);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'تم تحديث بيانات الخطة بنجاح.');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'تم حذف الخطة بنجاح.');
    }
}
