<?php

use App\Http\Controllers\admin\PaymentController;
use App\Http\Controllers\admin\PlanController;
use App\Http\Controllers\api\ApiHomeController;
use App\Http\Controllers\api\CarController;
use App\Http\Controllers\api\ChatController;
use App\Http\Controllers\api\LocationController;
use App\Http\Controllers\api\RideBookController;
use App\Http\Controllers\api\RideController;
use App\Http\Controllers\api\VehicleTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Storage;


Route::post('register',[AuthController::class,'register']);
Route::post('login', [AuthController::class,'login']);
Route::post('refresh', [AuthController::class,'refresh']);
Route::post('logout', [AuthController::class,'logout']);


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

    Route::get('/check_status', [AuthController::class, 'checkStatus']);

    Route::get('get_orders_data', [RideBookController::class, 'getOrdersData']);

    Route::get('get_plans', [RideController::class, 'getPlans']);
    Route::get('get_booking_equests', [RideBookController::class, 'getBookingRequests']);
    Route::post('/update-booking-status', [RideBookController::class, 'updateBookingStatus']);

  
    Route::get('me', [AuthController::class, 'me']);
    Route::post('edit_name_and_phone', [AuthController::class, 'updateNameAndPhone']);
    Route::get('get_koshy_app', [ApiHomeController::class, 'getKoshyApp']);
  

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
    Route::get('history', [RideBookController::class, 'getHistory']);
    
    Route::put('profile', [AuthController::class, 'updateProfile']);

});
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
