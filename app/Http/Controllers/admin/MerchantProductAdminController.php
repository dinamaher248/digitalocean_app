<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MerchantProductAdminController extends Controller
{
    public function index(Request $request)
    {
        $merchantId = (int) $request->get('merchant_id', 0);
        $ownerId = (int) $request->get('owner_id', 0);

        if ($merchantId > 0 || $ownerId > 0) {
            return $this->productsForOwner($request, $merchantId, $ownerId);
        }

        $ownersQuery = User::query()
            ->whereIn('title', ['merchant', 'vendor'])
            ->where(function ($q) {
                $q->where('is_admin', 0)->orWhereNull('is_admin');
            })
            ->with(['ownedMerchant' => function ($q) {
                $q->withCount([
                    'products',
                    'products as images_count' => function ($p) {
                        $p->whereNotNull('image')->where('image', '!=', '');
                    },
                ])->with('category');
            }]);

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $ownersQuery->where(function ($b) use ($q) {
                $b->where('name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhereHas('ownedMerchant', fn ($m) => $m->where('name', 'like', '%'.$q.'%'));
            });
        }

        $owners = $ownersQuery->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.sections.products.index', compact('owners'));
    }

    public function toggle(int $id)
    {
        $product = MerchantProduct::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);

        return back()->with('success', 'تم تحديث حالة المنتج');
    }

    public function destroy(int $id)
    {
        $product = MerchantProduct::findOrFail($id);
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return back()->with('success', 'تم حذف المنتج');
    }

    private function productsForOwner(Request $request, int $merchantId, int $ownerId)
    {
        $merchant = null;
        $owner = null;

        if ($merchantId > 0) {
            $merchant = Merchant::with('owner')->findOrFail($merchantId);
            $owner = $merchant->owner;
        } else {
            $owner = User::findOrFail($ownerId);
            $merchant = Merchant::with('owner')->where('owner_user_id', $owner->id)->first();
        }

        $query = MerchantProduct::with('merchant');
        if ($merchant) {
            $query->where('merchant_id', $merchant->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where('name', 'like', "%{$q}%");
        }

        if ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->get('status') === 'active') {
            $query->where('is_active', true);
        }

        $products = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.sections.products.index', compact('products', 'merchant', 'owner'));
    }
}
