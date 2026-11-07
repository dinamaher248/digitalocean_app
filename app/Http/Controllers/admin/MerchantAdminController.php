<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MerchantAdminController extends Controller
{
    public function index()
    {
        $merchants = Merchant::with('category')->orderByDesc('id')->paginate(20);

        return view('admin.sections.merchants.index', compact('merchants'));
    }

    public function create()
    {
        $categories = ServiceCategory::where('is_active', true)->orderBy('sort_order')->get();
        $owners = User::where('title', 'merchant')->orderBy('name')->get(['id', 'name', 'phone']);

        return view('admin.sections.merchants.create', compact('categories', 'owners'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'owner_user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'wallet_number' => 'nullable|string|max:30',
            'pay_cash' => 'nullable|boolean',
            'pay_insta' => 'nullable|boolean',
            'pay_on_delivery' => 'nullable|boolean',
            'pay_wallet' => 'nullable|boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('merchants', 'public');
        }

        $data['pay_cash'] = $request->boolean('pay_cash');
        $data['pay_insta'] = $request->boolean('pay_insta');
        $data['pay_on_delivery'] = $request->boolean('pay_on_delivery');
        $data['pay_wallet'] = $request->boolean('pay_wallet');
        $data['is_open'] = true;
        $data['is_active'] = true;

        $merchant = Merchant::create($data);

        return redirect()
            ->route('admin.merchants.products', $merchant->id)
            ->with('success', 'تم إضافة المتجر بنجاح');
    }

    public function products(int $merchantId)
    {
        $merchant = Merchant::with(['products', 'category'])->findOrFail($merchantId);

        return view('admin.sections.merchants.products', compact('merchant'));
    }

    public function storeProduct(Request $request, int $merchantId)
    {
        $merchant = Merchant::with('category')->findOrFail($merchantId);
        $limit = $merchant->category?->maxImages() ?? ServiceCategory::DEFAULT_MAX_IMAGES;

        if ($merchant->products()->count() >= $limit) {
            return back()->with('error', 'الحد الأقصى ' . $limit . ' صورة لهذا القسم');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('merchant_products', 'public');
        }

        $merchant->products()->create($data);

        return back()->with('success', 'تم إضافة المنتج');
    }

    public function destroyProduct(int $merchantId, int $productId)
    {
        $product = MerchantProduct::where('merchant_id', $merchantId)->findOrFail($productId);
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return back()->with('success', 'تم حذف المنتج');
    }
}
