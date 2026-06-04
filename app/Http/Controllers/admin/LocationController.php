<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * عرض قائمة الأماكن:
     *  - view=tree  (افتراضي): شجرة محافظات → مدن
     *  - view=flat: جدول مسطح مع فلتر type
     */
  public function index(Request $request)
{
    $type     = $request->get('type', 'governorate'); // governorate | zone | district | all
    $parentId = $request->get('parent_id');

    $query = Location::with('parent')
        ->orderBy('sort_order')
        ->orderBy('name_ar');

    if ($type !== 'all') {
        $query->where('type', $type);
    }

    // لو مفيش parent_id ومحافظات → هات بس اللي مالهاش parent
    if ($parentId) {
        $query->where('parent_id', $parentId);
    } elseif ($type === 'governorate') {
        $query->whereNull('parent_id');
    }

    $locations = $query->get();
    $parent    = $parentId ? Location::find($parentId) : null;

    $typeLabel = match ($type) {
        'governorate' => 'المحافظات',
        'zone'        => 'المناطق',
        'district'    => 'الأحياء / الأقسام',
        default       => 'كل الأماكن',
    };

    return view('admin.sections.locations.index', compact('locations', 'type', 'parent', 'typeLabel'));
}


    public function create(Request $request)
    {
        $type = $request->get('type', Location::TYPE_GOVERNORATE);

        $parents = collect();
        if ($type === Location::TYPE_AREA) {
            $parents = Location::governorates()->ordered()->get();
        }

        $location = new Location();

        return view('admin.sections.locations.create', [
            'location' => $location,
            'type'     => $type,
            'parents'  => $parents,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'       => 'required|in:governorate,area',
            'name_ar'    => 'required|string|max:255',
            'name_en'    => 'nullable|string|max:255',
            'parent_id'  => 'nullable|required_if:type,area|exists:locations,id',
            'is_primary' => 'nullable|boolean',
            'is_active'  => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'type.required'         => 'نوع المكان مطلوب.',
            'type.in'               => 'نوع المكان غير صحيح.',
            'name_ar.required'      => 'الاسم بالعربية مطلوب.',
            'parent_id.required_if' => 'يجب اختيار محافظة للمدينة.',
            'parent_id.exists'      => 'المحافظة غير موجودة.',
        ]);

        $data['is_primary'] = $request->boolean('is_primary');
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = $request->input('sort_order', 0);

        $location = Location::create($data);

        return redirect()
            ->route('admin.locations.index', [
                'view' => 'tree',
                // لو النوع مدينة هنرجع برضه لشجرة المحافظات
            ])
            ->with('success', 'تمت إضافة المكان بنجاح.');
    }

    public function edit(Location $location)
    {
        $type = $location->type;

        $parents = collect();
        if ($type === Location::TYPE_AREA) {
            $parents = Location::governorates()->ordered()->get();
        }

        return view('admin.sections.locations.edit', [
            'location' => $location,
            'type'     => $type,
            'parents'  => $parents,
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $data = $request->validate([
            'type'       => 'required|in:governorate,area',
            'name_ar'    => 'required|string|max:255',
            'name_en'    => 'nullable|string|max:255',
            'parent_id'  => 'nullable|required_if:type,area|exists:locations,id',
            'is_primary' => 'nullable|boolean',
            'is_active'  => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'type.required'         => 'نوع المكان مطلوب.',
            'type.in'               => 'نوع المكان غير صحيح.',
            'name_ar.required'      => 'الاسم بالعربية مطلوب.',
            'parent_id.required_if' => 'يجب اختيار محافظة للمدينة.',
            'parent_id.exists'      => 'المحافظة غير موجودة.',
        ]);

        $data['is_primary'] = $request->boolean('is_primary');
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = $request->input('sort_order', 0);

        $location->update($data);

        return redirect()
            ->route('admin.locations.index', ['view' => 'tree'])
            ->with('success', 'تم تحديث بيانات المكان بنجاح.');
    }

    public function destroy(Location $location)
    {
        if ($location->children()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'لا يمكن حذف هذا المكان لوجود أماكن تابعة له.');
        }

        $location->delete();

        return redirect()
            ->route('admin.locations.index', ['view' => 'tree'])
            ->with('success', 'تم حذف المكان بنجاح.');
    }
}
