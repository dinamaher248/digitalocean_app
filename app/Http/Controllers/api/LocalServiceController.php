<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\ServiceCategory;
use App\Models\ServiceDeliveryOffer;
use App\Models\RideBook;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\FcmService;
use App\Services\MerchantOrderNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LocalServiceController extends Controller
{
    public function categories()
    {
        $items = \App\Support\CategoryCatalog::all();
        if ($items === []) {
            $items = ServiceCategory::query()
                ->orderBy('id')
                ->get()
                ->map(fn (ServiceCategory $c) => [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'name' => $c->name,
                    'icon' => $c->icon,
                    'image' => $c->icon,
                    'hub_section' => $c->billingSection(),
                    'max_images' => $c->maxImages(),
                ]);
        }

        return response()->json(['status_code' => 200, 'data' => $items]);
    }

    public function merchants(Request $request, string $slug)
    {
        $category = ServiceCategory::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $lat = $request->query('lat');
        $lng = $request->query('lng');

        $merchants = Merchant::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->get()
            ->map(function (Merchant $m) use ($lat, $lng) {
                $distanceM = $this->distanceMeters($lat, $lng, $m->lat, $m->lng);

                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'image' => $m->image,
                    'address' => $m->address,
                    'is_open' => $m->is_open,
                    'avg_rating' => round((float) ($m->avg_rating ?? 0), 1),
                    'ratings_count' => (int) ($m->ratings_count ?? 0),
                    'distance_m' => $distanceM,
                    'distance_label' => $this->formatDistance($distanceM),
                    'payment_methods' => $this->paymentMethods($m),
                ];
            })
            ->sortBy('distance_m')
            ->values();

        return response()->json([
            'status_code' => 200,
            'category' => [
                'slug' => $category->slug,
                'name' => $category->name,
                'title_nearby' => $category->name . ' قريبة منك',
            ],
            'data' => $merchants,
        ]);
    }

    public function merchantDetail(int $id)
    {
        $merchant = Merchant::with(['products' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->limit(10), 'category'])
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'status_code' => 200,
            'data' => [
                'id' => $merchant->id,
                'name' => $merchant->name,
                'image' => $merchant->image,
                'address' => $merchant->address,
                'phone' => $merchant->phone,
                'lat' => $merchant->lat,
                'lng' => $merchant->lng,
                'avg_rating' => round((float) ($merchant->avg_rating ?? 0), 1),
                'ratings_count' => (int) ($merchant->ratings_count ?? 0),
                'category_name' => $merchant->category?->name,
                'working_hours' => $merchant->working_hours ?? [],
                'is_open' => $merchant->is_open,
                'payment_methods' => $this->paymentMethods($merchant),
                'wallet_number' => $merchant->wallet_number,
                'wallet_owner_display' => MerchantOwnerController::maskWalletOwnerName($merchant->wallet_owner_name),
                'wallet_type' => $merchant->wallet_type,
                'products' => $merchant->products->map(fn (MerchantProduct $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                    'description' => $p->description,
                    'image' => $p->image,
                ])->values(),
            ],
        ]);
    }

    public function storeOrder(Request $request)
    {
        $user = Auth::guard('api')->user();

        $data = $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'product_id' => 'required|exists:merchant_products,id',
            'quantity' => 'required|integer|min:1|max:99',
            'notes' => 'nullable|string|max:1000',
            'delivery_address' => 'required|string|max:500',
            'delivery_details' => 'nullable|string|max:500',
            'delivery_lat' => 'nullable|numeric',
            'delivery_lng' => 'nullable|numeric',
            'payment_method' => 'required|in:wallet,cash,insta,on_delivery',
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $merchant = Merchant::with('owner')->findOrFail($data['merchant_id']);
        $product = MerchantProduct::where('merchant_id', $merchant->id)
            ->where('id', $data['product_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if (!$this->merchantSupportsPayment($merchant, $data['payment_method'])) {
            return response()->json([
                'status_code' => 422,
                'message' => 'طريقة الدفع غير متاحة لدى هذا المتجر',
            ], 422);
        }

        if ($data['payment_method'] === 'wallet' && !$request->hasFile('payment_proof')) {
            return response()->json([
                'status_code' => 422,
                'message' => 'يجب رفع صورة التحويل للمحفظة الإلكترونية',
            ], 422);
        }

        $quantity = (int) $data['quantity'];
        $totalPrice = round($product->price * $quantity, 2);
        $commission = \App\Support\VendorOrderBilling::dueAmountForMerchant($merchant, $totalPrice);

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store('service_orders/payments', 'public');
        }

        $status = $data['payment_method'] === 'wallet'
            ? 'awaiting_payment_review'
            : 'new';

        $order = ServiceOrder::createCompatible([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'store_id' => $merchant->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => $product->price,
            'product_description' => $product->description,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'product_total' => $totalPrice,
            'notes' => $data['notes'] ?? $data['delivery_details'] ?? null,
            'delivery_address' => $data['delivery_address']
                ?: ($request->input('fulfillment_type') === 'pickup' ? 'استلام من المكان' : ''),
            'delivery_details' => $data['delivery_details'] ?? null,
            'delivery_lat' => $data['delivery_lat'] ?? $request->input('delivery_lat') ?? 0,
            'delivery_lng' => $data['delivery_lng'] ?? $request->input('delivery_lng') ?? 0,
            'fulfillment_type' => $request->input('fulfillment_type'),
            'payment_method' => $data['payment_method'],
            'payment_proof' => $proofPath,
            'wallet_receipt' => $proofPath,
            'status' => $status === 'awaiting_payment_review' ? 'new' : $status,
            'commission_amount' => $commission,
        ]);

        $this->notifyMerchantOwner($merchant, $order);
        app(MerchantOrderNotificationService::class)->notifyCustomerOrderSent($user, $order);

        return response()->json([
            'status_code' => 201,
            'message' => $status === 'awaiting_payment_review'
                ? 'تم إرسال الطلب وبانتظار مراجعة الدفع'
                : 'تم إرسال الطلب بنجاح',
            'data' => $this->formatOrder($order->fresh(['merchant'])),
        ], 201);
    }

    public function storeCustomOrder(Request $request)
    {
        $user = Auth::guard('api')->user();

        $data = $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'custom_request_text' => 'nullable|string|max:2000',
            'custom_request_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'customer_contact_phone' => 'required|string|max:30',
            'delivery_address' => 'required|string|max:500',
            'delivery_details' => 'nullable|string|max:500',
            'delivery_lat' => 'nullable|numeric',
            'delivery_lng' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
        ]);

        $text = trim($data['custom_request_text'] ?? '');
        if ($text === '' && !$request->hasFile('custom_request_image')) {
            return response()->json([
                'status_code' => 422,
                'message' => 'يجب كتابة الطلب أو رفع صورة على الأقل',
            ], 422);
        }

        $merchant = Merchant::with('owner')->findOrFail($data['merchant_id']);

        $imagePath = null;
        if ($request->hasFile('custom_request_image')) {
            $imagePath = $request->file('custom_request_image')->store('custom_orders', 'public');
        }

        $order = ServiceOrder::createCompatible([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'store_id' => $merchant->id,
            'order_type' => 'custom',
            'product_id' => null,
            'product_name' => 'اطلب أي شيء',
            'product_price' => 0,
            'product_description' => $text !== '' ? $text : null,
            'custom_request_text' => $text !== '' ? $text : null,
            'custom_request_image' => $imagePath,
            'quantity' => 1,
            'total_price' => 0,
            'product_total' => 0,
            'notes' => $data['notes'] ?? null,
            'customer_contact_phone' => $data['customer_contact_phone'],
            'delivery_address' => $data['delivery_address'],
            'delivery_details' => $data['delivery_details'] ?? null,
            'delivery_lat' => $data['delivery_lat'] ?? null,
            'delivery_lng' => $data['delivery_lng'] ?? null,
            'payment_method' => 'on_delivery',
            'status' => 'awaiting_merchant_offer',
            'commission_amount' => 0,
        ]);

        $this->notifyMerchantOwner($merchant, $order);
        app(MerchantOrderNotificationService::class)->notifyCustomerOrderSent($user, $order);

        return response()->json([
            'status_code' => 201,
            'message' => 'تم إرسال طلبك — بانتظار عرض من المتجر',
            'data' => $this->formatOrder($order->fresh(['merchant'])),
        ], 201);
    }

    public function showOrder(int $id)
    {
        $user = Auth::guard('api')->user();

        $order = ServiceOrder::with('merchant')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'status_code' => 200,
            'data' => $this->formatOrder($order),
        ]);
    }

    public function acceptCustomOffer(int $id)
    {
        $user = Auth::guard('api')->user();

        $order = ServiceOrder::with(['merchant.owner'])
            ->where('user_id', $user->id)
            ->where('order_type', 'custom')
            ->findOrFail($id);

        if ($order->status !== 'offer_sent') {
            return response()->json([
                'status_code' => 422,
                'message' => 'لا يمكن قبول هذا العرض',
            ], 422);
        }

        if ($order->merchant_offer_price === null || (float) $order->merchant_offer_price <= 0) {
            return response()->json([
                'status_code' => 422,
                'message' => 'لا يمكن قبول عرض بدون سعر',
            ], 422);
        }

        $price = round((float) $order->merchant_offer_price, 2);
        $commission = \App\Support\VendorOrderBilling::dueAmountForMerchant($order->merchant, $price);

        DB::transaction(function () use ($order, $price, $commission) {
            $order->update([
                'status' => 'offer_accepted',
                'total_price' => $price,
                'product_price' => $price,
                'commission_amount' => $commission,
            ]);
            // عمولة التاجر تُحسب فقط عند إكمال التسليم للعميل، مش عند قبول العرض.
        });

        $order = $order->fresh(['merchant']);
        if ($order->merchant) {
            app(MerchantOrderNotificationService::class)->notifyCustomOfferAccepted(
                $order->merchant,
                $order,
            );
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'تم قبول العرض',
            'data' => $this->formatOrder($order),
        ]);
    }

    public function rejectCustomOffer(int $id)
    {
        $user = Auth::guard('api')->user();

        $order = ServiceOrder::with('merchant')
            ->where('user_id', $user->id)
            ->where('order_type', 'custom')
            ->findOrFail($id);

        if ($order->status !== 'offer_sent') {
            return response()->json([
                'status_code' => 422,
                'message' => 'لا يمكن رفض هذا العرض',
            ], 422);
        }

        $order->update(['status' => 'offer_rejected']);

        if ($order->merchant) {
            app(MerchantOrderNotificationService::class)->notifyCustomOfferRejected(
                $order->merchant,
                $order,
            );
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'تم رفض العرض',
            'data' => $this->formatOrder($order->fresh(['merchant'])),
        ]);
    }

    public function myOrders()
    {
        $user = Auth::guard('api')->user();

        $orders = ServiceOrder::with('merchant')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (ServiceOrder $o) => $this->formatOrder($o));

        return response()->json(['status_code' => 200, 'data' => $orders]);
    }

    public function driverOrders()
    {
        $driver = Auth::guard('api')->user();

        if ($driver->title !== 'driver') {
            return response()->json(['status_code' => 403, 'message' => 'للسائقين فقط'], 403);
        }

        if ($this->normalizeVehicle($driver->vehicle_type) !== 'motorcycle') {
            return response()->json(['status_code' => 200, 'data' => []]);
        }

        if (!(bool) $driver->is_online) {
            return response()->json(['status_code' => 200, 'data' => []]);
        }

        $orders = ServiceOrder::with(['merchant', 'user'])
            ->where('status', 'awaiting_driver')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn (ServiceOrder $o) => $this->formatOrderForDriver($o, $driver->id));

        return response()->json(['status_code' => 200, 'data' => $orders]);
    }

    public function submitDeliveryOffer(Request $request, int $orderId)
    {
        $driver = Auth::guard('api')->user();

        if ($driver->title !== 'driver') {
            return response()->json(['status_code' => 403, 'message' => 'للسائقين فقط'], 403);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $order = ServiceOrder::findOrFail($orderId);

        if ($order->status !== 'awaiting_driver') {
            return response()->json(['status_code' => 422, 'message' => 'الطلب غير متاح للتوصيل'], 422);
        }

        $offer = ServiceDeliveryOffer::updateOrCreate(
            [
                'service_order_id' => $order->id,
                'driver_id' => $driver->id,
            ],
            [
                'amount' => $data['amount'],
                'status' => 'pending',
            ]
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'تم إرسال عرض التوصيل',
            'data' => [
                'id' => $offer->id,
                'amount' => $offer->amount,
                'status' => $offer->status,
            ],
        ]);
    }

    public function acceptDeliveryOffer(int $orderId, int $offerId)
    {
        $order = ServiceOrder::findOrFail($orderId);
        $offer = ServiceDeliveryOffer::where('service_order_id', $order->id)
            ->where('id', $offerId)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($order->status !== 'awaiting_driver') {
            return response()->json(['status_code' => 422, 'message' => 'تم تعيين سائق مسبقًا'], 422);
        }

        DB::transaction(function () use ($order, $offer) {
            $offer->update(['status' => 'accepted']);
            $order->deliveryOffers()
                ->where('id', '!=', $offer->id)
                ->update(['status' => 'rejected']);

            $bookingId = null;
            if (Schema::hasTable('ride_book') && $order->user_id) {
                $bookingPayload = [
                    'driver_id' => $offer->driver_id,
                    'user_id' => $order->user_id,
                    'amount' => $offer->amount,
                    'msg' => $order->product_name,
                    'pick_loc' => $order->merchant?->name,
                    'target_loc' => $order->delivery_address,
                    'status' => 'in_progress',
                ];
                if (Schema::hasColumn('ride_book', 'service_order_id')) {
                    $bookingPayload['service_order_id'] = $order->id;
                }
                $bookingId = RideBook::create($bookingPayload)->id;
            }

            $update = [
                'status' => 'driver_assigned',
                'driver_id' => $offer->driver_id,
                'accepted_delivery_offer_id' => $offer->id,
                'delivery_fee' => $offer->amount,
            ];
            if ($bookingId && Schema::hasColumn('service_orders', 'ride_book_id')) {
                $update['ride_book_id'] = $bookingId;
            }
            $order->update($update);
        });

        $order = $order->fresh(['merchant', 'user', 'driver']);
        $customer = $order->user;
        if ($customer) {
            app(FcmService::class)->sendToUser(
                $customer,
                'تم تعيين سائق لطلبك',
                'سائق في الطريق لتوصيل طلبك من ' . ($order->merchant->name ?? ''),
                'service_order_status',
                (string) $order->id,
            );
        }

        app(MerchantOrderNotificationService::class)->notifyOrderStatus(
            $order,
            'تحديث حالة الطلب',
            'تم تعيين سائق لطلب #' . $order->id,
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'تم قبول عرض السائق',
            'data' => $this->formatOrder($order),
        ]);
    }

    private function notifyMerchantOwner(Merchant $merchant, ServiceOrder $order): void
    {
        app(MerchantOrderNotificationService::class)->notifyNewOrder($merchant, $order);
    }

    private function merchantSupportsPayment(Merchant $merchant, string $method): bool
    {
        return match ($method) {
            'wallet' => $merchant->pay_wallet,
            'cash' => $merchant->pay_cash,
            'insta' => $merchant->pay_insta,
            'on_delivery' => $merchant->pay_on_delivery,
            default => false,
        };
    }

    private function paymentMethods(Merchant $merchant): array
    {
        $methods = [];
        if ($merchant->pay_wallet) {
            $methods[] = ['key' => 'wallet', 'label' => 'محفظة إلكترونية'];
        }
        if ($merchant->pay_cash) {
            $methods[] = ['key' => 'cash', 'label' => 'كاش'];
        }
        if ($merchant->pay_insta) {
            $methods[] = ['key' => 'insta', 'label' => 'انستا باي'];
        }
        if ($merchant->pay_on_delivery) {
            $methods[] = ['key' => 'on_delivery', 'label' => 'دفع عند الاستلام'];
        }

        return $methods;
    }

    private function formatOrder(ServiceOrder $order): array
    {
        $isCustom = $order->order_type === 'custom';

        return [
            'id' => $order->id,
            'order_type' => $order->order_type ?? 'catalog',
            'merchant_id' => $order->merchant_id ?? $order->store_id,
            'merchant_name' => $order->merchant?->name ?? $order->store?->name,
            'product_name' => $order->product_name,
            'product_price' => $order->product_price,
            'product_description' => $order->product_description,
            'custom_request_text' => $order->custom_request_text,
            'custom_request_image' => $order->custom_request_image,
            'quantity' => $order->quantity,
            'total_price' => $order->total_price,
            'merchant_offer_price' => $order->merchant_offer_price,
            'merchant_offer_notes' => $order->merchant_offer_notes,
            'notes' => $order->notes,
            'customer_contact_phone' => $order->customer_contact_phone,
            'delivery_address' => $order->delivery_address,
            'delivery_details' => $order->delivery_details,
            'payment_method' => $order->payment_method,
            'payment_method_label' => $this->paymentLabel($order->payment_method),
            'status' => $order->status,
            'status_label' => $this->statusLabel($order->status),
            'delivery_fee' => $order->delivery_fee,
            'commission_amount' => $order->commission_amount,
            'can_accept_offer' => $isCustom
                && $order->status === 'offer_sent'
                && $order->merchant_offer_price !== null
                && (float) $order->merchant_offer_price > 0,
            'can_reject_offer' => $isCustom && $order->status === 'offer_sent',
            'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function formatOrderForDriver(ServiceOrder $order, int $driverId): array
    {
        $myOffer = $order->deliveryOffers()
            ->where('driver_id', $driverId)
            ->first();

        $pickupDistance = $this->distanceMeters(
            $order->delivery_lat,
            $order->delivery_lng,
            $order->merchant?->lat,
            $order->merchant?->lng,
        );

        return [
            'id' => $order->id,
            'pickup_from' => $order->merchant?->name,
            'pickup_address' => $order->merchant?->address,
            'deliver_to' => $order->delivery_address,
            'distance_label' => $this->formatDistance($pickupDistance),
            'payment_method_label' => $this->paymentLabel($order->payment_method),
            'product_name' => $order->product_name,
            'total_price' => $order->total_price,
            'my_offer' => $myOffer ? [
                'id' => $myOffer->id,
                'amount' => $myOffer->amount,
                'status' => $myOffer->status,
            ] : null,
        ];
    }

    private function paymentLabel(string $method): string
    {
        return match ($method) {
            'wallet' => 'محفظة إلكترونية',
            'cash' => 'كاش',
            'insta' => 'انستا باي',
            'on_delivery' => 'دفع عند الاستلام',
            default => $method,
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'new' => 'طلب جديد',
            'awaiting_payment_review' => 'بانتظار مراجعة الدفع',
            'awaiting_merchant_offer' => 'بانتظار عرض المتجر',
            'offer_sent' => 'عرض بانتظار ردك',
            'offer_rejected' => 'تم رفض العرض',
            'offer_accepted' => 'تم قبول العرض — جاري التجهيز',
            'merchant_rejected' => 'مرفوض من المتجر',
            'preparing' => 'جاري التجهيز',
            'awaiting_driver' => 'جاري التنفيذ — بانتظار سائق',
            'driver_invited' => 'جاري التنفيذ — بانتظار السائق',
            'driver_assigned' => 'جاري التنفيذ — السائق في الطريق',
            'handed_to_driver' => 'جاري التوصيل — مع السائق',
            'in_delivery' => 'جاري التوصيل إليك',
            'completed' => 'مكتمل (تم التسليم)',
            'cancelled' => 'ملغي',
            default => $status,
        };
    }

    private function distanceMeters($lat1, $lng1, $lat2, $lng2): ?int
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $earth = 6371000;
        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        $dLng = deg2rad((float) $lng2 - (float) $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * sin($dLng / 2) ** 2;

        return (int) round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function formatDistance(?int $meters): string
    {
        if ($meters === null) {
            return '—';
        }
        if ($meters < 1000) {
            return $meters . ' متر';
        }

        return round($meters / 1000, 1) . ' كم';
    }

    private function normalizeVehicle(?string $type): string
    {
        $type = strtolower((string) $type);

        return $type === 'toktok' ? 'tricycle' : $type;
    }
}
