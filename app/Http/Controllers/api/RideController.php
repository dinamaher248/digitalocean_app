<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Car;
use App\Models\Category;
use App\Models\File;
use App\Models\Image;
use App\Models\Plan;
use App\Models\Ride;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RideController extends Controller
{

   public function getPlans()
{
    try {
        $plans = Plan::orderBy('price')->get();
    } catch (\Throwable $e) {
        $plans = collect();
    }

    $user = Auth::guard('api')->user();
    $subscription = null;

    if ($user) {
        try {
            $subscription = Subscription::where('user_id', $user->id)
                ->where(function ($query) {
                    $query->where('status', 'pending')
                        ->orWhere('status', 'approved');
                })
                ->latest()
                ->first();
        } catch (\Throwable $e) {
            $subscription = null;
        }
    }

    return response()->json([
        'status_code' => 200,
        'plans' => $plans->values(),
        'subscription' => $subscription,
    ]);
}



    // List all upcoming rides (public)
    public function index()
    {
        $rides = Ride::with('car', 'user')
            ->get();


        $rides = $rides->map(function ($ride) {
            $ride->start_name = $this->cleanLocation($ride->start_name);
            $ride->dest_name = $this->cleanLocation($ride->dest_name);
            return $ride;
        });
            
            return response()->json([
                'status_code' => 200,
                'message' => $rides
            ], 200);
    }
private function cleanLocation($location)
{
    $parts = explode(',', $location);
    $filtered = array_filter($parts, function ($part) {
        $part = trim($part);
        return !is_numeric($part) &&
               !preg_match('/^\d{5,}$/', $part) && // postal code
               $part !== 'مصر' &&
               !preg_match('/^\d+[A-Za-z]?$/', $part); // building numbers
    });

    return implode(', ', array_map('trim', $filtered));
}

    // Create new ride
    public function store(Request $request)
    {
        $request->validate([
            'car_id' => 'required|exists:car,id',
            // 'from_location' => 'required|string|max:255',
            // 'to_location' => 'required|string|max:255',
            // 'departure_time' => 'required|date|after:now',
            'price' => 'nullable|numeric|min:0',
            // 'notes' => 'nullable|string',
        ]);
// return response()->json([
//             'status_code' => 333,
//             'message' => $request->all()
//         ], 333);
        // Ensure car belongs to user
        $car = Car::where('id', $request->car_id)
                  ->where('user_id', Auth::id())
                  ->firstOrFail();

        $ride = Ride::create([
            'user_id' => Auth::id(),
            'car_id' => $car->id,
            'departure_date' => $request->departure_date,
            'departure_time' => $request->departure_time,
            'start_lat' => $request->start_lat,
            'start_lng' => $request->start_lng,
            'start_name' => $request->start_name,
            'dest_lat' => $request->dest_lat,
            'dest_lng' => $request->dest_lng,
            'dest_name' => $request->dest_name,
            'seats' => $request->seats,
            'price' => $request->price,
            'description' => $request->description,
        ]);
        return response()->json([
            'status_code' => 200,
            'message' => $ride
        ], 200);
        return response()->json(['message' => 'Ride created successfully', 'ride' => $ride], 201);
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

    public function destroy($id)
    {
        $ride = Ride::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $ride->delete();

        return response()->json(['message' => 'Ride deleted successfully']);
    }
}
