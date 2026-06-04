<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Car;
use App\Models\Category;
use App\Models\File;
use App\Models\Image;
use App\Models\PatientCase;
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
    public function addPlan(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'months' => 'required|integer|min:1',
    ]);

    $plan = Plan::create([
        'name' => $request->name,
        'price' => $request->price,
        'months' => $request->months,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Plan added successfully.',
        'plan' => $plan,
    ]);
}
public function approveSubscription($id)
{
    $subscription = Subscription::findOrFail($id);

    if ($subscription->status !== 'pending') {
        return response()->json(['status' => 'error', 'message' => 'Subscription already processed.'], 400);
    }

    $subscription->status = 'approved';
    $subscription->start_date = now();
    $subscription->end_date = now()->addMonths($subscription->plan->months);
    $subscription->save();

    return response()->json(['status' => 'success', 'message' => 'Subscription approved.']);
}

    public function index()
    {
        $rides = Ride::with('car', 'user')
            // ->where('departure_time', '>', now())
            // ->orderBy('departure_time')
            ->get();

           
        return view('admin.sections.rides.rides', compact('rides'));
    }

    public function add(){
        $cars = Car::all();
        $users = User::all();
        return view('admin.sections.rides.add_ride', compact('cars', 'users'));
    }

    /*
    _token: "0NjwNZKPTj2g7cYGCcL0P1BTw0j93UJzBnt0An3d",
    car_id: "11",
    departure_date: "2025-06-17",
    departure_time: "14:27",
    start_point: "2, Al Mouarikh Mohamed Refaat Street, Cairo, 11843, Egypt",
    start_point_coords: "30.12423, 31.36372",
    start_lat: "30.12423",
    start_lng: "31.36372",
    start_name: "2, Al Mouarikh Mohamed Refaat Street, Cairo, 11843, Egypt",
    end_point: "Karmoz Street, Al Amod, Alexandria, 21559, Egypt",
    end_point_coords: "31.18765, 29.89645",
    end_lat: "31.18765",
    end_lng: "29.89645",
    end_name: "Karmoz Street, Al Amod, Alexandria, 21559, Egypt",
    seats: "12",
    price: "850",
    description: "t drtdr tdr tdrt drt drt drt rd",
    /admin/store_ride: null,
    ride_image: { }
    */

    public function store(Request $request)
    {
        // $request->validate([
        //     'car_id' => 'required|exists:cars,id',
        //     'user_id' => 'required|exists:users,id',
        //     'start_name' => 'required|string|max:255',
        //     'dest_name' => 'required|string|max:255',
        //     'departure_time' => 'required|date',
        // ]);
        // return response()->json([
        //     'status_code' => 200,
        //     'message' => $request->all()
        // ], 200);
        // $car = Car::where('id', $request->car_id)
        //           ->where('user_id', Auth::guard('web')->user()->id)
        //           ->firstOrFail();

        // $ride = Ride::create([
        //     'user_id' => Auth::guard('web')->user()->id,
        //     'car_id' => $car->id,
        //     'departure_date' => $request->departure_date,
        //     'departure_time' => $request->departure_time,
        //     'start_lat' => $request->start_lat,
        //     'start_lng' => $request->start_lng,
        //     'start_name' => $request->start_name,
        //     'dest_lat' => $request->dest_lat,
        //     'dest_lng' => $request->dest_lng,
        //     'dest_name' => $request->dest_name,
        //     'seats' => $request->seats,
        //     'price' => $request->price,
        //     'description' => $request->description,
        // ]);

        $ride = new Ride();
    $ride->user_id = Auth::guard('web')->user()->id;
    $ride->car_id = $request->car_id;
    $ride->departure_date = $request->departure_date;
    $ride->departure_time = $request->departure_time;
    $ride->start_lat = $request->start_lat;
    $ride->start_lng = $request->start_lng;
    $ride->start_name = $request->start_name;
    $ride->dest_lat = $request->end_lat;
    $ride->dest_lng = $request->end_lng;
    $ride->dest_name = $request->end_name;
    $ride->seats = $request->seats;
    $ride->price = $request->price;
    $ride->description = $request->description;
    $ride->type = 1; // Default status, can be changed later
    
    // https://koshycoding.com/wheely/storage/app/public/
    // Handle image upload if present
    if ($request->hasFile('ride_image')) {
        $imagePath = $request->file('ride_image')->store('rides', 'public');
        $ride->image = $imagePath; // Store the path in the database
    }

    // Save the ride to the database
    $ride->save();

        return redirect()->route('admin.rides.index')->with('success', 'Ride created successfully.');
    }

}
