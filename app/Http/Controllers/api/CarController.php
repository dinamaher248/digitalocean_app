<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Car;
use App\Models\Category;
use App\Models\File;
use App\Models\Image;
use App\Models\PatientCase;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarController extends Controller
{

    public function index()
    {
        $rides = Ride::with('car', 'user')
            ->where('departure_time', '>', now())
            ->orderBy('departure_time')
            ->get();

        return response()->json($rides);
    }

    public function storeCar(Request $request)
    {
        $request->validate([
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'seats' => 'required|integer|min:1|max:9',
            'color' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
    
        $carImagePath = $request->file('image')?->store('car_images', 'public');
    
        $car = Car::create([
            'user_id' => Auth::guard('api')->user()->id,
            'brand' => $request->brand,
            'model' => $request->model,
            'seats' => $request->seats,
            'trans_type' => $request->trans_type,
            'color' => $request->color,
            'description' => $request->description,
            'car_image' => $carImagePath,
        ]);
        $car->car_image_url = $carImagePath ? asset("storage/app/public/$carImagePath") : null;
        return response()->json($car, 201);
       return response()->json([
            'status_code' => 200,
            'message' => $car
        ]);
        return response()->json([
            'message' => 'Car added successfully',
            'car' => $car,
            'car_image_url' => $carImagePath ? asset("storage/$carImagePath") : null,
            'license_image_url' => $licenseImagePath ? asset("storage/$licenseImagePath") : null
        ], 201);
     
    }

    public function show($id)
    {
        $ride = Ride::with('car', 'user')->findOrFail($id);
        return response()->json($ride);
    }

    public function userRides()
    {
        $rides = Ride::with('car')->where('user_id', Auth::id())->get();
        return response()->json($rides);
    }


    public function getMyCars()
    {
        $cars = Car::where('user_id', Auth::id())->get();
        return response()->json($cars);
    }

    public function destroy($id)
    {
        $ride = Ride::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $ride->delete();

        return response()->json(['message' => 'Ride deleted successfully']);
    }


}
