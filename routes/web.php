<?php

use App\Http\Controllers\admin\AdminHomeController;
use App\Http\Controllers\admin\LocationController;
use App\Http\Controllers\admin\PlanController;
use App\Http\Controllers\admin\RideController;
use App\Http\Controllers\api\CarController;
use App\Http\Controllers\api\RideBookController;
use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('/paymob', function(Request $request){
    Log::info('Paymob redirect received', $request->all());
    $success = $request->query('success', 'unknown');
    $errorOccured = $request->query('error_occured', 'unknown');
    Log::info("Paymob success: $success, error_occured: $errorOccured");
    return view('paymob');
    // return redirect()->back();
});

Route::middleware('auth')->group(function () {

});

// privacy policy
Route::get('/privacy_policy', function () {
    return view('privacy_policy');
})->name('privacy.policy');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', function () {
        return view('admin.login');
    })->name('login');

    Route::post('/loginPost', [AdminHomeController::class, 'login'])->name('login.post');

    Route::middleware('is_admin')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');
        Route::post('/logout', [AdminHomeController::class, 'logout'])->name('logout');

        Route::get('/account/password', [\App\Http\Controllers\admin\AdminPasswordController::class, 'edit'])
            ->name('password.edit');
        Route::put('/account/password', [\App\Http\Controllers\admin\AdminPasswordController::class, 'update'])
            ->name('password.update');

        Route::get('/dashboard', [\App\Http\Controllers\admin\DashboardAdminController::class, 'index'])
            ->name('dashboard');
        Route::get('/add_case', function () {
            return view('admin.sections.cases.add_case');
        })->name('case.add');
        Route::post('/store_case', [AdminHomeController::class, 'storeCase'])->name('case.store');

        Route::get('registration_request', [AdminHomeController::class, 'getSubscriptionRequests'])->name('register_request.index');
        Route::get('/admin/subscriptions', [AdminHomeController::class, 'index'])->name('subscriptions.index');
        Route::get('/admin/subscriptions/update/{id}/{status}', [AdminHomeController::class, 'updateStatus'])->name('subscriptions.update');


          // Plans CRUD
    Route::get('plans', [\App\Http\Controllers\admin\PlanController::class, 'index'])
        ->name('plans.index');

    Route::get('plans/create', [\App\Http\Controllers\admin\PlanController::class, 'create'])
        ->name('plans.create');

    Route::post('plans', [\App\Http\Controllers\admin\PlanController::class, 'store'])
        ->name('plans.store');

    Route::get('plans/{plan}/edit', [\App\Http\Controllers\admin\PlanController::class, 'edit'])
        ->name('plans.edit');

    Route::put('plans/{plan}', [\App\Http\Controllers\admin\PlanController::class, 'update'])
        ->name('plans.update');

    Route::delete('plans/{plan}', [\App\Http\Controllers\admin\PlanController::class, 'destroy'])
        ->name('plans.destroy');
    Route::post('plans/{plan}/toggle', [\App\Http\Controllers\admin\PlanController::class, 'toggle'])
        ->name('plans.toggle');


         Route::prefix('locations')->name('locations.')->group(function () {

        // قائمة الأماكن (افتراضياً المحافظات)
        Route::get('/', [LocationController::class, 'index'])->name('index');

        // صفحة إنشاء مكان جديد
        Route::get('/create', [LocationController::class, 'create'])->name('create');

        // حفظ مكان جديد
        Route::post('/', [LocationController::class, 'store'])->name('store');

        // صفحة تعديل مكان
        Route::get('/{location}/edit', [LocationController::class, 'edit'])->name('edit');

        // تحديث مكان
        Route::put('/{location}', [LocationController::class, 'update'])->name('update');

        // حذف مكان
        Route::delete('/{location}', [LocationController::class, 'destroy'])->name('destroy');

    });

    
        // Users
        Route::get('/users', [\App\Http\Controllers\admin\UserAdminController::class, 'index'])->name('user.index');
        Route::post('/user/{id}/suspend', [\App\Http\Controllers\admin\UserAdminController::class, 'toggleSuspend'])->name('user.suspend');
        Route::post('/user/{id}/approve', [\App\Http\Controllers\admin\UserAdminController::class, 'approve'])->name('user.approve');
        Route::post('/user/{id}/reject', [\App\Http\Controllers\admin\UserAdminController::class, 'reject'])->name('user.reject');
        Route::get('/user/delete/{id}', [\App\Http\Controllers\admin\UserAdminController::class, 'destroy'])->name('user.delete');
        Route::get('/user/{id}', [\App\Http\Controllers\admin\UserAdminController::class, 'show'])->name('user.details');
        Route::get('/add_user', function () { return view('admin.sections.users.add_user'); })->name('user.add');
        Route::post('/store_user', [AdminHomeController::class, 'storeUser'])->name('user.store');

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\admin\ServiceCategoryAdminController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\admin\ServiceCategoryAdminController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\admin\ServiceCategoryAdminController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [\App\Http\Controllers\admin\ServiceCategoryAdminController::class, 'edit'])->name('edit');
            Route::put('/{category}', [\App\Http\Controllers\admin\ServiceCategoryAdminController::class, 'update'])->name('update');
            Route::delete('/{category}', [\App\Http\Controllers\admin\ServiceCategoryAdminController::class, 'destroy'])->name('destroy');
            Route::post('/{category}/toggle', [\App\Http\Controllers\admin\ServiceCategoryAdminController::class, 'toggle'])->name('toggle');
        });

        Route::prefix('merchant-products')->name('products.')->group(function () {
            Route::get('/', [\App\Http\Controllers\admin\MerchantProductAdminController::class, 'index'])->name('index');
            Route::post('/{id}/toggle', [\App\Http\Controllers\admin\MerchantProductAdminController::class, 'toggle'])->name('toggle');
            Route::delete('/{id}', [\App\Http\Controllers\admin\MerchantProductAdminController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('vehicle-types')->name('vehicle_types.')->group(function () {
            Route::get('/', [\App\Http\Controllers\admin\VehicleTypeAdminController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\admin\VehicleTypeAdminController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\admin\VehicleTypeAdminController::class, 'store'])->name('store');
            Route::get('/{vehicleType}/edit', [\App\Http\Controllers\admin\VehicleTypeAdminController::class, 'edit'])->name('edit');
            Route::put('/{vehicleType}', [\App\Http\Controllers\admin\VehicleTypeAdminController::class, 'update'])->name('update');
            Route::delete('/{vehicleType}', [\App\Http\Controllers\admin\VehicleTypeAdminController::class, 'destroy'])->name('destroy');
            Route::post('/{vehicleType}/toggle', [\App\Http\Controllers\admin\VehicleTypeAdminController::class, 'toggle'])->name('toggle');
        });

        Route::get('commission-settings', [\App\Http\Controllers\admin\CommissionSettingsController::class, 'edit'])
            ->name('commission.edit');
        Route::post('commission-settings', [\App\Http\Controllers\admin\CommissionSettingsController::class, 'update'])
            ->name('commission.update');

        Route::get('payment-settings', [\App\Http\Controllers\admin\PaymentSettingsController::class, 'edit'])
            ->name('payment_settings.edit');
        Route::post('payment-settings', [\App\Http\Controllers\admin\PaymentSettingsController::class, 'update'])
            ->name('payment_settings.update');

        Route::prefix('admins')->name('admins.')->group(function () {
            Route::get('/', [\App\Http\Controllers\admin\AdminUserController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\admin\AdminUserController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\admin\AdminUserController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [\App\Http\Controllers\admin\AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\admin\AdminUserController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\admin\AdminUserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [\App\Http\Controllers\admin\RegistrationDocumentAdminController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\admin\RegistrationDocumentAdminController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\admin\RegistrationDocumentAdminController::class, 'store'])->name('store');
            Route::get('/{document}/edit', [\App\Http\Controllers\admin\RegistrationDocumentAdminController::class, 'edit'])->name('edit');
            Route::put('/{document}', [\App\Http\Controllers\admin\RegistrationDocumentAdminController::class, 'update'])->name('update');
            Route::delete('/{document}', [\App\Http\Controllers\admin\RegistrationDocumentAdminController::class, 'destroy'])->name('destroy');
            Route::post('/{document}/toggle', [\App\Http\Controllers\admin\RegistrationDocumentAdminController::class, 'toggle'])->name('toggle');
        });

        Route::get('/rides', [RideController::class, 'index'])->name('rides.index');
        Route::get('/add_ride', [RideController::class, 'add'])->name('ride.add');
        Route::post('/store_ride', [RideController::class, 'store'])->name('ride.post');
        Route::get('/edit_ride/{id}', [RideController::class, 'edit'])->name('ride.edit');
        Route::post('/update_ride/{id}', [RideController::class, 'update'])->name('ride.update');

        Route::get('/rides_books', [RideBookController::class, 'index'])->name('rides.book.index');
        Route::get('/add_ride_book', [RideController::class, 'add'])->name('ride.book.add');
        Route::post('/store_ride_book', [RideController::class, 'store'])->name('ride.book.post');
        Route::get('/edit_ride_book/{id}', [RideController::class, 'edit'])->name('ride.book.edit');
        Route::post('/update_ride_book/{id}', [RideController::class, 'update'])->name('ride.book.update');

        Route::get('/cars', [CarController::class, 'index'])->name('cars.index');
        Route::get('/add_car', [CarController::class, 'add'])->name('cars.add');
        Route::post('/store_car', [CarController::class, 'store'])->name('cars.post');
        Route::get('/edit_car/{id}', [CarController::class, 'edit'])->name('cars.edit');
        Route::post('/update_car/{id}', [CarController::class, 'update'])->name('cars.update');

        Route::get('/add_payment', function(){ return view('admin.sections.settings.add_payment'); })->name('add_payment');
        Route::get('/map_settings', function(){ return view('admin.sections.settings.map_settings'); })->name('map_settings');
        Route::get('/privacy_policy', function(){ return view('admin.sections.settings.privacy_policy'); })->name('privacy_policy');

        Route::get('send_notification_by_topic', [AdminHomeController::class, 'sendNotificationByTopic'])->name('send_notification_by_topic_get');
        Route::post('send_notification_to_topic', [AdminHomeController::class, 'sendNotificationToTopicPost'])->name('send_notification_to_topic_post');

        Route::get('wallet_settings', [\App\Http\Controllers\admin\WalletSettingsController::class, 'edit'])
            ->name('wallet_settings.edit');
        Route::post('wallet_settings', [\App\Http\Controllers\admin\WalletSettingsController::class, 'update'])
            ->name('wallet_settings.update');

        Route::get('welcome_settings', [\App\Http\Controllers\admin\WelcomeSettingsController::class, 'edit'])
            ->name('welcome_settings.edit');
        Route::post('welcome_settings', [\App\Http\Controllers\admin\WelcomeSettingsController::class, 'update'])
            ->name('welcome_settings.update');

        Route::get('merchants', [\App\Http\Controllers\admin\MerchantAdminController::class, 'index'])
            ->name('merchants.index');
        Route::get('merchants/create', [\App\Http\Controllers\admin\MerchantAdminController::class, 'create'])
            ->name('merchants.create');
        Route::post('merchants', [\App\Http\Controllers\admin\MerchantAdminController::class, 'store'])
            ->name('merchants.store');
        Route::get('merchants/{id}/products', [\App\Http\Controllers\admin\MerchantAdminController::class, 'products'])
            ->name('merchants.products');
        Route::post('merchants/{id}/products', [\App\Http\Controllers\admin\MerchantAdminController::class, 'storeProduct'])
            ->name('merchants.products.store');
        Route::delete('merchants/{merchantId}/products/{productId}', [\App\Http\Controllers\admin\MerchantAdminController::class, 'destroyProduct'])
            ->name('merchants.products.destroy');

        Route::get('service-orders', [\App\Http\Controllers\admin\ServiceOrderAdminController::class, 'index'])
            ->name('service_orders.index');
        Route::post('service-orders/{id}/accept', [\App\Http\Controllers\admin\ServiceOrderAdminController::class, 'accept'])
            ->name('service_orders.accept');
        Route::post('service-orders/{id}/reject', [\App\Http\Controllers\admin\ServiceOrderAdminController::class, 'reject'])
            ->name('service_orders.reject');
        Route::post('service-orders/{orderId}/offers/{offerId}/accept', [\App\Http\Controllers\admin\ServiceOrderAdminController::class, 'acceptDriverOffer'])
            ->name('service_orders.accept_driver_offer');

        Route::get('settlements', [\App\Http\Controllers\admin\SettlementAdminController::class, 'index'])
            ->name('settlements.index');
        Route::post('settlements/{id}/approve', [\App\Http\Controllers\admin\SettlementAdminController::class, 'approve'])
            ->name('settlements.approve');
        Route::post('settlements/{id}/reject', [\App\Http\Controllers\admin\SettlementAdminController::class, 'reject'])
            ->name('settlements.reject');


     
        
    });
});

require __DIR__.'/auth.php';
