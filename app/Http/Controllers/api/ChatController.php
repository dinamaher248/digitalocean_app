<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Car;
use App\Models\Messages;
use App\Models\PatientCase;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{



    public function getMessages($ride_id, $partner_id)
    {
        $userId = Auth::guard('api')->user()->id;
        $partnerId = $partner_id;
        $rideId = $ride_id;

        $messages = Messages::where(function ($query) use ($userId, $partnerId, $rideId) {
            $query->where('sender_id', $userId)
            ->where('receiver_id', $partnerId)
            ->where('ride_id', $rideId);
        })->orWhere(function ($query) use ($userId, $partnerId, $rideId) {
            $query->where('sender_id', $partnerId)
            ->where('receiver_id', $userId)
            ->where('ride_id', $rideId);
        })->orderBy('created_at', 'asc')->get();

        return response()->json([
            'status_code' => 200,
            'message' => $messages
        ], 200);

        return response()->json($messages);
    }

    public function getLatestImageMessage($ride_id, $partner_id, $last_msg_id)
    {
        $userId = Auth::guard('api')->user()->id;
        $rideId = $ride_id;
        $partnerId = $partner_id;
        $lastMessageId = $last_msg_id;
    
        $query = Messages::where('receiver_id', $userId)
            ->where('sender_id', $partnerId)
            ->where('ride_id', $rideId)
            // ->where('messagelastMessageId_type', 'image') // Assuming you have this column
            ->orderBy('id', 'desc');
    
        if ($lastMessageId) {
            $query->where('id', '>', $lastMessageId);
        }
    
        $latestImage = $query->first();
        if($latestImage == null){
            return response()->json([
                'status_code' => 204,
                'message' => 'No new messages'
            ], 204);      
        }
        return response()->json([
            'status_code' => 200,
            'message' => $latestImage
        ], 200);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'ride_id' => 'required|exists:ride,id',
            'message' => 'required|string'
        ]);

        $message = Messages::create([
            'sender_id' => Auth::guard('api')->user()->id,
            'receiver_id' => $request->receiver_id,
            'ride_id' => $request->ride_id,
            'message' => $request->message,
        ]);

        return response()->json([
            'status_code' => 200,
            'message' => $message
        ], 200);

        return response()->json(['success' => true, 'message' => $message]);
    }




    public function index()
    {
        $rides = Ride::with('car', 'user')
            ->get();

            return response()->json([
                'status_code' => 200,
                'message' => $rides
            ], 200);
    }

    // Create new ride
    public function store(Request $request)
    {     
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
            // 'status' => $request->status,
        ]);
        return response()->json([
            'status_code' => 200,
            'message' => $ride
        ], 200);
        return response()->json(['message' => 'Ride created successfully', 'ride' => $ride], 201);
    }

    // Show specific ride
    public function show($id)
    {
        $ride = Ride::with('car', 'user')->findOrFail($id);
        return response()->json($ride);
    }

    // List user-created rides
    public function userRides()
    {
        $rides = Ride::with('car')->where('user_id', Auth::id())->get();
        return response()->json($rides);
    }

    // Delete a ride
    public function destroy($id)
    {
        $ride = Ride::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $ride->delete();

        return response()->json(['message' => 'Ride deleted successfully']);
    }
}
