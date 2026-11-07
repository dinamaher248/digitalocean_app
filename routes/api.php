<?php

use App\Http\Controllers\admin\PaymentController;
use App\Http\Controllers\admin\PlanController;
use App\Http\Controllers\api\ApiHomeController;
use App\Http\Controllers\api\CarController;
use App\Http\Controllers\api\ChatController;
use App\Http\Controllers\api\LocationController;
use App\Http\Controllers\api\RideBookController;
use App\Http\Controllers\api\RideController;
use App\Http\Controllers\api\DriverPanelController;
use App\Http\Controllers\api\LocalServiceController;
use App\Http\Controllers\api\MerchantOwnerController;
use App\Http\Controllers\api\ServicesController;
use App\Http\Controllers\api\VendorController;
use App\Http\Controllers\api\TripRequestController;
use App\Http\Controllers\api\VehicleTypeController;
use App\Http\Controllers\api\ApiBiddingController;
use App\Http\Controllers\api\OrderTrackingController;
use App\Http\Controllers\api\ApiAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Storage;


Route::post('register',[AuthController::class,'register']);
Route::post('send-otp', [AuthController::class, 'sendOtp']);
Route::post('verify-otp', [AuthController::class, 'verifyOtpAndRegister']);
Route::post('forgot-password', [AuthController::class, 'sendPasswordResetOtp']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('login', [AuthController::class,'login']);
Route::post('refresh', [AuthController::class,'refresh']);
Route::post('logout', [AuthController::class,'logout']);
Route::get('get_koshy_app', [ApiHomeController::class, 'getKoshyApp']);
Route::get('welcome-screen', [ApiHomeController::class, 'getWelcomeScreen']);
Route::get('/required-documents', [ApiHomeController::class, 'requiredDocuments']);
Route::get('/required_documents', [ApiHomeController::class, 'requiredDocuments']);
Route::get('/registration/documents', [ApiHomeController::class, 'requiredDocuments']);


    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/{governorate}/areas', [LocationController::class, 'children']);
    Route::get('/vehicle-types', [VehicleTypeController::class, 'index']);


Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('subscribe',[AuthController::class,'subscribe']);
    Route::get('get_available_drivers',[AuthController::class,'getAvailableDrivers']);
    Route::get('drivers',[AuthController::class,'getDrivers']);
    
    Route::post('toggle_online', [AuthController::class, 'toggleOnlineStatus']);
    Route::post('driver/location', [AuthController::class, 'updateDriverLocation']);
    Route::post('update_driver_location', [AuthController::class, 'updateDriverLocation']);

    Route::get('/check_status', [AuthController::class, 'checkStatus']);

    Route::get('get_orders_data', [RideBookController::class, 'getOrdersData']);

    Route::get('get_plans', [RideController::class, 'getPlans']);
    Route::get('get_booking_equests', [RideBookController::class, 'getBookingRequests']);
    Route::post('/update-booking-status', [RideBookController::class, 'updateBookingStatus']);

  
    Route::get('me', [AuthController::class, 'me']);
    Route::post('edit_name_and_phone', [AuthController::class, 'updateNameAndPhone']);
  

    //Ride
    Route::get('/rides', [RideController::class, 'index']);
    Route::post('/ride', [RideController::class, 'store']);
    Route::get('/rides/{id}', [RideController::class, 'show']);
    Route::get('/user/rides', [RideController::class, 'userRides']);
    Route::delete('/rides/{id}', [RideController::class, 'destroy']);


    //RideBook
    Route::get('/ridebooks', [RideBookController::class, 'index']);
    Route::post('/ridebook', [RideBookController::class, 'store']);
    Route::get('/ridebooks/{id}', [RideBookController::class, 'show']);
    Route::put('/ridebooks/{id}', [RideBookController::class, 'update']);
    Route::delete('/ridebooks/{id}', [RideBookController::class, 'destroy']);
    Route::post('/trip_finished', [RideBookController::class, 'rideFinished']); 
    Route::post('/withdraw', [ApiHomeController::class, 'withdraw']); 

    Route::get('/chat/{ride_id}/{partner_id}', [ChatController::class, 'getMessages']);
    Route::get('/last_chat/{ride_id}/{partner_id}/{last_msg_id}', [ChatController::class, 'getLatestImageMessage']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);





    Route::get('/home', [ApiHomeController::class, 'index']);
    Route::get('/history', [RideBookController::class, 'getHistory']);

    Route::post('/trip-requests', [TripRequestController::class, 'store']);
    Route::get('/trip-requests/mine', [TripRequestController::class, 'myRequests']);
    Route::get('/trip-requests/driver', [TripRequestController::class, 'indexForDriver']);
    Route::get('/trip-requests/{id}', [TripRequestController::class, 'show']);
    Route::get('/trip-requests/{id}/offers', [TripRequestController::class, 'offers']);
    Route::post('/trip-requests/{id}/offers', [TripRequestController::class, 'submitOffer']);
    Route::post('/trip-requests/{tripId}/offers/{offerId}/accept', [TripRequestController::class, 'acceptOffer']);
    Route::post('/trip-requests/{id}/cancel', [TripRequestController::class, 'cancel']);

    // Bidding system (InDrive-style)
    Route::post('/bidding-trips/create', [ApiBiddingController::class, 'create']);
    Route::get('/bidding-trips/my-trips', [ApiBiddingController::class, 'myTrips']);
    Route::get('/bidding-trips/{id}/details', [ApiBiddingController::class, 'details']);
    Route::get('/bidding-trips/{id}/offers', [ApiBiddingController::class, 'offers']);
    Route::post('/bidding-trips/{id}/offers', [ApiBiddingController::class, 'submitOffer']);
    Route::post('/bidding-trips/{id}/cancel', [ApiBiddingController::class, 'cancel']);
    Route::get('/bidding-trips/{id}', [ApiBiddingController::class, 'show']);
    Route::post('/bidding-offers/{id}/accept', [ApiBiddingController::class, 'acceptOffer']);

    Route::get('/merchant/profile', [MerchantOwnerController::class, 'profile']);
    Route::post('/merchant/profile', [MerchantOwnerController::class, 'saveProfile']);
    Route::get('/merchant/payment-methods', [MerchantOwnerController::class, 'paymentMethods']);
    Route::post('/merchant/payment-methods', [MerchantOwnerController::class, 'savePaymentMethods']);
    Route::get('/merchant/products', [MerchantOwnerController::class, 'products']);
    Route::post('/merchant/products', [MerchantOwnerController::class, 'storeProduct']);
    Route::post('/merchant/products/{productId}', [MerchantOwnerController::class, 'updateProduct']);
    Route::delete('/merchant/products/{productId}', [MerchantOwnerController::class, 'deleteProduct']);
    Route::get('/merchant/orders', [MerchantOwnerController::class, 'orders']);
    Route::get('/merchant/orders/{orderId}', [MerchantOwnerController::class, 'showOrder']);
    Route::post('/merchant/orders/{orderId}/accept', [MerchantOwnerController::class, 'acceptOrder']);
    Route::post('/merchant/orders/{orderId}/reject', [MerchantOwnerController::class, 'rejectOrder']);
    Route::post('/merchant/orders/{orderId}/confirm-payment', [MerchantOwnerController::class, 'confirmPayment']);
    Route::post('/merchant/orders/{orderId}/reject-payment', [MerchantOwnerController::class, 'rejectPayment']);
    Route::post('/merchant/orders/{orderId}/send-offer', [MerchantOwnerController::class, 'sendCustomOffer']);
    Route::get('/merchant/orders/{orderId}/drivers', [MerchantOwnerController::class, 'availableDriversForOrder']);
    Route::post('/merchant/orders/{orderId}/assign-driver', [MerchantOwnerController::class, 'assignDriver']);
    Route::get('/merchant/dues', [MerchantOwnerController::class, 'duesSummary']);
    Route::post('/merchant/settlement-requests', [MerchantOwnerController::class, 'submitSettlement']);

    Route::get('/vendor/store', [VendorController::class, 'store']);
    Route::post('/vendor/store/update', [VendorController::class, 'updateStore']);
    Route::get('/vendor/products', [VendorController::class, 'products']);
    Route::post('/vendor/products', [VendorController::class, 'storeProduct']);
    Route::post('/vendor/products/{id}/toggle', [VendorController::class, 'toggleProduct']);
    Route::get('/vendor/orders', [VendorController::class, 'orders']);
    Route::post('/vendor/orders/{id}/handle', [VendorController::class, 'handleOrder']);
    Route::get('/vendor/wallet', [VendorController::class, 'wallet']);
    Route::post('/vendor/wallet/settle', [VendorController::class, 'settleWallet']);
    Route::post('/vendor/wallet/billing-mode', [VendorController::class, 'updateBillingMode']);

    Route::get('/local-services/categories', [LocalServiceController::class, 'categories']);
    Route::get('/local-services/categories/{slug}/merchants', [LocalServiceController::class, 'merchants']);
    Route::get('/local-services/merchants/{id}', [LocalServiceController::class, 'merchantDetail']);
    Route::post('/local-services/orders', [LocalServiceController::class, 'storeOrder']);
    Route::post('/local-services/custom-orders', [LocalServiceController::class, 'storeCustomOrder']);
    Route::get('/local-services/orders/mine', [LocalServiceController::class, 'myOrders']);
    Route::get('/local-services/orders/driver', [LocalServiceController::class, 'driverOrders']);
    Route::get('/local-services/orders/{orderId}', [LocalServiceController::class, 'showOrder']);
    Route::post('/local-services/orders/{orderId}/accept-offer', [LocalServiceController::class, 'acceptCustomOffer']);
    Route::post('/local-services/orders/{orderId}/reject-offer', [LocalServiceController::class, 'rejectCustomOffer']);
    Route::post('/local-services/orders/{orderId}/delivery-offers', [LocalServiceController::class, 'submitDeliveryOffer']);
    Route::post('/local-services/orders/{orderId}/delivery-offers/{offerId}/accept', [LocalServiceController::class, 'acceptDeliveryOffer']);

    Route::get('/services/stores', [ServicesController::class, 'stores']);
    Route::get('/services/stores/{id}', [ServicesController::class, 'storeDetail']);
    Route::post('/services/orders/checkout', [ServicesController::class, 'checkout']);

    Route::get('/driver/available-orders', [DriverPanelController::class, 'availableOrders']);
    Route::post('/driver/orders/service_order/{id}/accept-invite', [DriverPanelController::class, 'acceptServiceInvitation']);
    Route::get('/driver/my-orders', [DriverPanelController::class, 'myCompletedOrders']);
    Route::match(['GET', 'POST'], '/driver/orders/{type}/{id}/complete', [DriverPanelController::class, 'completeOrder']);
    Route::match(['GET', 'POST'], '/driver/orders/{type}/{id}/progress', [DriverPanelController::class, 'updateProgress']);
    Route::match(['GET', 'POST'], '/driver/orders/{type}/{id}/going-to-pickup', [DriverPanelController::class, 'goToPickup']);
    Route::match(['GET', 'POST'], '/driver/orders/{type}/{id}/picked-up', [DriverPanelController::class, 'markPickedUp']);
    Route::match(['GET', 'POST'], '/order-tracking/{type}/{id}/progress', [DriverPanelController::class, 'updateProgress']);
    Route::get('/driver/dues', [DriverPanelController::class, 'duesSummary']);
    Route::post('/driver/settlement-requests', [DriverPanelController::class, 'submitSettlement']);

    Route::get('/driver/available-bids', [DriverPanelController::class, 'availableBids']);
    Route::post('/driver/trips/{id}/make-offer', [DriverPanelController::class, 'makeOffer']);
    Route::get('/driver/my-trips', [DriverPanelController::class, 'myTrips']);
    Route::get('/driver/wallet', [DriverPanelController::class, 'wallet']);
    Route::post('/driver/wallet/settle', [DriverPanelController::class, 'settleWallet']);
    Route::post('/driver/wallet/billing-mode', [DriverPanelController::class, 'updateBillingMode']);
    
    Route::post('/ratings', [\App\Http\Controllers\api\RatingController::class, 'store']);

    Route::get('/order-tracking/{type}/{id}', [OrderTrackingController::class, 'show']);
    Route::post('/order-tracking/{type}/{id}/hand-to-driver', [OrderTrackingController::class, 'handToDriver']);
    Route::post('/order-tracking/{type}/{id}/cancel', [OrderTrackingController::class, 'cancelActive']);

    Route::put('profile', [AuthController::class, 'updateProfile']);
    Route::get('profile', [AuthController::class, 'getProfile']);
    Route::post('profile/update', [AuthController::class, 'updateProfilePost']);
    Route::post('set_fcm_token', [AuthController::class, 'setFcmToken']);

    // Admin mobile/API dashboard
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [ApiAdminController::class, 'stats']);
        Route::get('/users', [ApiAdminController::class, 'users']);
        Route::post('/users/{id}/toggle', [ApiAdminController::class, 'toggleUser']);
        Route::get('/users/{id}/review', [ApiAdminController::class, 'reviewUser']);
    });

});
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
