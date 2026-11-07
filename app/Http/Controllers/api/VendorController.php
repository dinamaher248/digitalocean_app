<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Support\VendorOrderBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Thin adapter for Vendor Interface endpoints.
 * Reuses MerchantOwnerController business logic.
 */
class VendorController extends Controller
{
    public function store(Request $request)
    {
        $profile = app(MerchantOwnerController::class)->profile();
        $payment = app(MerchantOwnerController::class)->paymentMethods();

        $profileData = $profile->getData(true);
        $paymentData = $payment->getData(true);

        $data = $profileData['data'] ?? $profileData;
        $pay = $paymentData['data'] ?? $paymentData;

        return response()->json([
            'status_code' => 200,
            'data' => array_merge(
                is_array($data) ? $data : [],
                ['payment' => is_array($pay) ? $pay : []]
            ),
        ]);
    }

    public function updateStore(Request $request)
    {
        $profileResponse = app(MerchantOwnerController::class)->saveProfile($request);

        if ($profileResponse->getStatusCode() >= 400) {
            return $profileResponse;
        }

        if ($request->has('pay_on_delivery') || $request->has('pay_wallet')) {
            $payResponse = app(MerchantOwnerController::class)->savePaymentMethods($request);
            if ($payResponse->getStatusCode() >= 400) {
                return $payResponse;
            }
        }

        return $this->store($request);
    }

    public function products()
    {
        return app(MerchantOwnerController::class)->products();
    }

    public function storeProduct(Request $request)
    {
        return app(MerchantOwnerController::class)->storeProduct($request);
    }

    public function toggleProduct(int $id)
    {
        $merchantController = app(MerchantOwnerController::class);
        $productsResponse = $merchantController->products();
        $payload = $productsResponse->getData(true);
        $products = $payload['data']['products'] ?? [];

        $product = null;
        foreach ($products as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                $product = $item;
                break;
            }
        }

        if (!$product) {
            return response()->json([
                'status_code' => 404,
                'message' => 'المنتج غير موجود',
            ], 404);
        }

        $request = Request::create('/dummy', 'POST', [
            'name' => $product['name'],
            'price' => $product['price'],
            'description' => $product['description'] ?? null,
            'is_active' => empty($product['is_active']) ? 1 : 0,
        ]);
        $request->headers->set('Authorization', request()->header('Authorization'));
        $request->setUserResolver(fn () => request()->user());

        return $merchantController->updateProduct($request, $id);
    }

    public function orders()
    {
        return app(MerchantOwnerController::class)->orders();
    }

    public function handleOrder(Request $request, int $id)
    {
        $action = $request->input('action', 'accept');
        $controller = app(MerchantOwnerController::class);

        return match ($action) {
            'accept' => $controller->acceptOrder($id),
            'reject' => $controller->rejectOrder($id),
            'confirm_payment' => $controller->confirmPayment($id),
            'reject_payment' => $controller->rejectPayment($id),
            default => response()->json([
                'status_code' => 422,
                'message' => 'إجراء غير معروف',
            ], 422),
        };
    }

    public function wallet()
    {
        $response = app(MerchantOwnerController::class)->duesSummary();
        $payload = $response->getData(true);
        $data = $payload['data'] ?? [];

        return response()->json([
            'status_code' => 200,
            'data' => [
                'due_amount' => (float) ($data['dues_balance'] ?? 0),
                'dues_balance' => (float) ($data['dues_balance'] ?? 0),
                'commission_rate' => (float) ($data['commission_rate'] ?? 10),
                'completed_orders_count' => (int) ($data['completed_orders_count'] ?? 0),
                'bookings_count' => (int) ($data['bookings_count'] ?? $data['completed_orders_count'] ?? 0),
                'wallet_number' => $data['wallet_number'] ?? '',
                'instapay_number' => $data['instapay_number'] ?? '',
                'hub_section' => $data['hub_section'] ?? 'orders',
                'billing_mode' => $data['billing_mode'] ?? null,
                'commission_option_enabled' => (bool) ($data['commission_option_enabled'] ?? false),
                'fixed_fee_option_enabled' => (bool) ($data['fixed_fee_option_enabled'] ?? false),
                'order_commission_percent' => (float) ($data['order_commission_percent'] ?? 0),
                'order_fixed_fee' => (float) ($data['order_fixed_fee'] ?? 0),
                'fixed_fee_per_order' => (float) ($data['fixed_fee_per_order'] ?? 0),
                'debt_limit' => (float) ($data['debt_limit'] ?? 0),
            ],
        ]);
    }

    public function updateBillingMode(Request $request)
    {
        $data = $request->validate([
            'billing_mode' => 'required|in:commission,fixed_fee',
        ]);

        $user = Auth::user();
        $merchant = Merchant::with('category')->where('owner_user_id', $user?->id)->first();
        $section = $merchant?->category?->billingSection() ?? 'orders';

        $available = $data['billing_mode'] === 'fixed_fee'
            ? VendorOrderBilling::fixedFeeOptionEnabled($section)
            : VendorOrderBilling::commissionOptionEnabled($section);

        if (!$available) {
            return response()->json([
                'status_code' => 422,
                'message' => 'هذا الخيار غير متاح حالياً',
            ], 422);
        }

        if ($user && Schema::hasColumn('users', 'vendor_billing_mode')) {
            $user->vendor_billing_mode = $data['billing_mode'];
            $user->save();
        }

        return $this->wallet();
    }

    public function settleWallet(Request $request)
    {
        if ($request->hasFile('receipt_image') && !$request->hasFile('payment_proof')) {
            $request->files->set('payment_proof', $request->file('receipt_image'));
        }

        return app(MerchantOwnerController::class)->submitSettlement($request);
    }
}
