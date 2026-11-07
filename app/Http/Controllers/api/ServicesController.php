<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Thin adapter for the Services Super App endpoints.
 * Reuses LocalServiceController business logic.
 */
class ServicesController extends Controller
{
    public function stores(Request $request)
    {
        $category = (string) $request->query('category', '');
        if ($category === '') {
            return response()->json([
                'status_code' => 422,
                'message' => 'category مطلوب',
            ], 422);
        }

        return app(LocalServiceController::class)->merchants($request, $category);
    }

    public function storeDetail(int $id)
    {
        return app(LocalServiceController::class)->merchantDetail($id);
    }

    public function checkout(Request $request)
    {
        if ($request->filled('store_id') && !$request->filled('merchant_id')) {
            $request->merge(['merchant_id' => $request->input('store_id')]);
        }

        if ($request->hasFile('wallet_receipt') && !$request->hasFile('payment_proof')) {
            $request->files->set('payment_proof', $request->file('wallet_receipt'));
        }

        $method = $request->input('payment_method');
        if ($method === 'cod') {
            $request->merge(['payment_method' => 'on_delivery']);
        } elseif ($method === 'e_wallet') {
            $request->merge(['payment_method' => 'wallet']);
        }

        return app(LocalServiceController::class)->storeOrder($request);
    }
}
