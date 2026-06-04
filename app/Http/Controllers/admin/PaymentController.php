<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\File;
use App\Models\Image;
use App\Models\PatientCase;
use App\Models\Ride;
use App\Models\RideBook;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://accept.paymob.com/api/']);
    }

    public function initiatePayment(Request $request)
    {
        $tripId = $request->trip_id;
        $paymentMethod = $request->payment_method;
        $phone = $request->phone ?? '01234567890';

        // Find trip
        $trip = Ride::findOrFail($tripId);
        $amount = $trip->fee * 100; // Convert to cents

        // Create payment record
        $payment = RideBook::create([
            'trip_id' => $trip->id,
            'amount' => $trip->fee,
            'status' => 'pending'
        ]);

        // Step 1: Authenticate
        $authResponse = $this->client->post('auth/tokens', [
            'json' => ['api_key' => env('PAYMOB_API_KEY')]
        ]);
        $authToken = json_decode($authResponse->getBody())->token;

        // Step 2: Register Order
        $orderResponse = $this->client->post('ecommerce/orders', [
            'json' => [
                'auth_token' => $authToken,
                'delivery_needed' => false,
                'amount_cents' => $amount,
                'currency' => 'EGP',
                'items' => [['name' => 'Trip Fee', 'amount_cents' => $amount, 'quantity' => 1]]
            ]
        ]);
        $paymobOrder = json_decode($orderResponse->getBody());
        $payment->update(['paymob_order_id' => $paymobOrder->id]);

        // Step 3: Get Payment Key
        $integrationId = $this->getIntegrationId($paymentMethod);
        $paymentKeyResponse = $this->client->post('acceptance/payment_keys', [
            'json' => [
                'auth_token' => $authToken,
                'amount_cents' => $amount,
                'expiration' => 3600,
                'order_id' => $paymobOrder->id,
                'billing_data' => [
                    'email' => 'test@example.com',
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'phone_number' => $phone,
                    'city' => 'Cairo',
                    'country' => 'Egypt',
                    'street' => 'NA',
                    'building' => 'NA',
                    'floor' => 'NA',
                    'apartment' => 'NA'
                ],
                'currency' => 'EGP',
                'integration_id' => $integrationId
            ]
        ]);
        $paymentKey = json_decode($paymentKeyResponse->getBody())->token;

        // Prepare response
        $response = [
            'payment_token' => $paymentKey,
            'payment_id' => $payment->id
        ];
        if ($paymentMethod === 'visa') {
            $response['iframe_url'] = "https://accept.paymob.com/api/acceptance/iframes/".env('PAYMOB_IFRAME_ID')."?payment_token=$paymentKey";
        } elseif ($paymentMethod === 'fawry') {
            $response['fawry_reference'] = $paymentKey;
        } elseif ($paymentMethod === 'wallet') {
            $response['wallet_url'] = "https://accept.paymob.com/api/acceptance/payments/pay?payment_token=$paymentKey&source[type]=mobile_wallet&source[identifier]=$phone";
        }

        return response()->json($response);
    }

    private function getIntegrationId($method)
    {
        return match ($method) {
            'visa' => env('PAYMOB_CARD_INTEGRATION_ID'),
            'fawry' => env('PAYMOB_FAWRY_INTEGRATION_ID'),
            'wallet' => env('PAYMOB_WALLET_INTEGRATION_ID'),
            default => throw new \Exception('Invalid payment method')
        };
    }

    public function processedCallback(Request $request)
    {
        $payment = RideBook::where('paymob_order_id', $request->order)->first();
        if ($payment && $request->success == true) {
            $payment->update(['status' => 'paid']);
            $payment->trip->update(['status' => 'paid']);
        } elseif ($payment) {
            $payment->update(['status' => 'failed']);
        }
        return response()->json(['status' => 'success']);
    }

    public function responseCallback(Request $request)
    {
        $paymentId = RideBook::where('paymob_order_id', $request->order)->first()->id;
        $success = $request->success == true ? 'true' : 'false';
        return redirect()->to(env('FRONTEND_DEEP_LINK')."?payment_id=$paymentId&success=$success");
    }

    public function checkPaymentStatus(Request $request)
    {
        $payment = RideBook::find($request->payment_id);
        if ($payment) {
            return response()->json([
                'status' => $payment->status,
                'amount' => $payment->amount
            ]);
        }
        return response()->json(['error' => 'Payment not found'], 404);
    }
}
