<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class VehicleTypeAdminController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('vehicle_types')) {
            return view('admin.sections.vehicle_types.index', [
                'types' => collect(),
                'missingTable' => true,
            ]);
        }

        $query = VehicleType::query();
        if (Schema::hasColumn('vehicle_types', 'sort_order')) {
            $query->orderBy('sort_order');
        }
        $types = $query->orderBy('id')->get();

        return view('admin.sections.vehicle_types.index', [
            'types' => $types,
            'missingTable' => false,
        ]);
    }

    public function create()
    {
        return view('admin.sections.vehicle_types.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        VehicleType::create($data);

        return redirect()
            ->route('admin.vehicle_types.index')
            ->with('success', 'تم إضافة وسيلة النقل — ستظهر في التطبيق حسب التصنيف');
    }

    public function edit(VehicleType $vehicleType)
    {
        return view('admin.sections.vehicle_types.edit', [
            'type' => $vehicleType,
        ]);
    }

    public function update(Request $request, VehicleType $vehicleType)
    {
        $data = $this->validated($request, $vehicleType->id);
        $vehicleType->update($data);

        return redirect()
            ->route('admin.vehicle_types.index')
            ->with('success', 'تم تحديث وسيلة النقل');
    }

    public function destroy(VehicleType $vehicleType)
    {
        $vehicleType->delete();

        return back()->with('success', 'تم حذف وسيلة النقل');
    }

    public function toggle(VehicleType $vehicleType)
    {
        if (Schema::hasColumn('vehicle_types', 'is_active')) {
            $vehicleType->is_active = !$vehicleType->is_active;
            $vehicleType->save();
        }

        return back()->with('success', 'تم تحديث حالة التفعيل');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $keyRule = Rule::unique('vehicle_types', 'key');
        if ($ignoreId !== null) {
            $keyRule = $keyRule->ignore($ignoreId);
        }

        $rules = [
            'key' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', $keyRule],
            'name_ar' => 'required|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'service_radius_km' => 'required|numeric|min:0.5|max:500',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ];

        if (Schema::hasColumn('vehicle_types', 'category')) {
            $rules['category'] = 'required|in:quick_trip,goods';
        }

        $data = $request->validate($rules, [
            'key.regex' => 'المفتاح يجب أن يكون بالإنجليزية بدون مسافات (مثال: scooter)',
            'service_radius_km.required' => 'نطاق الظهور بالكيلومتر مطلوب',
            'category.required' => 'اختر التصنيف: مشوار سريع أو نقل بضائع',
        ]);

        $data['name'] = $data['name_ar'];
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['service_radius_km'] = (float) $data['service_radius_km'];
        if (isset($data['category'])) {
            $data['category'] = $data['category'] === VehicleType::CATEGORY_GOODS
                ? VehicleType::CATEGORY_GOODS
                : VehicleType::CATEGORY_QUICK;
        }

        return $data;
    }
}
