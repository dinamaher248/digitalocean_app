<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceCategoryAdminController extends Controller
{
    public function index()
    {
        $categories = ServiceCategory::query()
            ->withCount([
                'products as images_count' => function ($q) {
                    $q->whereNotNull('merchant_products.image')
                        ->where('merchant_products.image', '!=', '');
                },
                'products as items_count',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.sections.categories.index', compact('categories'));
    }

    public function create()
    {
        $this->ensureHubSectionColumn();
        $category = new ServiceCategory();

        return view('admin.sections.categories.create', compact('category'));
    }

    public function store(Request $request)
    {
        $this->ensureHubSectionColumn();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:service_categories,slug',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'hub_section' => 'required|in:orders,bookings,services',
            'max_images' => 'nullable|integer|min:1|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        if ($data['slug'] === '') {
            $data['slug'] = 'cat-' . Str::lower(Str::random(8));
        }
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        if (!Schema::hasColumn('service_categories', 'hub_section')) {
            unset($data['hub_section']);
        }
        if (Schema::hasColumn('service_categories', 'max_images')) {
            $data['max_images'] = $data['max_images'] ?? ServiceCategory::DEFAULT_MAX_IMAGES;
        } else {
            unset($data['max_images']);
        }

        if ($request->hasFile('image')) {
            $data['icon'] = $request->file('image')->store('categories', 'public');
        }
        unset($data['image']);

        ServiceCategory::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'تم إضافة القسم');
    }

    public function edit(ServiceCategory $category)
    {
        $this->ensureHubSectionColumn();

        return view('admin.sections.categories.edit', compact('category'));
    }

    public function update(Request $request, ServiceCategory $category)
    {
        $this->ensureHubSectionColumn();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:service_categories,slug,' . $category->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'hub_section' => 'required|in:orders,bookings,services',
            'max_images' => 'nullable|integer|min:1|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        if (!Schema::hasColumn('service_categories', 'hub_section')) {
            unset($data['hub_section']);
        }
        if (Schema::hasColumn('service_categories', 'max_images')) {
            $data['max_images'] = $data['max_images'] ?? $category->maxImages();
        } else {
            unset($data['max_images']);
        }

        if ($request->hasFile('image')) {
            if ($this->isStoredImage($category->icon)) {
                Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = $request->file('image')->store('categories', 'public');
        }
        unset($data['image']);

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'تم تحديث القسم');
    }

    public function destroy(ServiceCategory $category)
    {
        if ($category->merchants()->exists()) {
            return back()->with('error', 'لا يمكن حذف قسم مرتبط بمتاجر');
        }

        if ($this->isStoredImage($category->icon)) {
            Storage::disk('public')->delete($category->icon);
        }

        $category->delete();

        return back()->with('success', 'تم حذف القسم');
    }

    public function toggle(ServiceCategory $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        return back()->with('success', 'تم تحديث حالة القسم');
    }

    private function isStoredImage(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        return str_contains($value, '/') || (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', $value);
    }

    private function ensureHubSectionColumn(): void
    {
        if (!Schema::hasTable('service_categories') || Schema::hasColumn('service_categories', 'hub_section')) {
            return;
        }

        Schema::table('service_categories', function ($table) {
            $table->string('hub_section', 32)->default('orders')->after('slug');
        });
    }
}
