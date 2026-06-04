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
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        })->name('home');
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');
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
        Route::get('/users', [AdminHomeController::class, 'allUsers'])->name('user.index');
        Route::get('/user/{id}', [AdminHomeController::class, 'userDetails'])->name('user.details');
        Route::get('/user/edit/{id}', [AdminHomeController::class, 'editUser'])->name('user.edit');
        Route::post('/user/update/{id}', [AdminHomeController::class, 'updateUser'])->name('user.update');
        Route::get('/user/delete/{id}', [AdminHomeController::class, 'deleteUser'])->name('user.delete');
        Route::get('/user/verify/{id}', [AdminHomeController::class, 'verifyUser'])->name('user.verify');
        Route::get('/add_user', function(){ return view('admin.sections.users.add_user'); })->name('user.add');
        Route::post('/store_user', [AdminHomeController::class, 'storeUser'])->name('user.store');

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


     
        
    });
});

require __DIR__.'/auth.php';
