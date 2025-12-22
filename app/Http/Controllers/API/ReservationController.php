<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Mail\StudioReservation;
use Mail;
use Log;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $user = auth()->user();
        $reservations = $user->reservations()->with('reservable')->orderBy('created_at','desc')->get();
        return response()->json([
            'status'=>true,
            'message'=>'User reservations',
            'datas'=>$reservations
        ]);
    }

    public function store(Request $request)
    {
        Log::info($request->all());
        $user = auth()->user();
        $data = [
            'user_id'=>$user->id,
            'reservable_id'=>$request->get('reservable_id'),
            'reservable_type'=>$request->get('reservable_type'),
            'start_datetime'=>$request->get('start_datetime'),
            'price'=>$request->get('price'),
            'payment_method'=>$request->get('payment_method'),
            'end_datetime'=>$request->get('end_datetime'),
        ];

        $reservation = Reservation::create($data);

        $reservation = Reservation::with('user')->find($reservation->id);
        
        Mail::to([$user->email,'bob.fleifel@gmail.com'])->send(new StudioReservation($reservation));

        return response()->json([
            'status'=>true,
            'message'=>'Reservation Created Successfully',
            'data'=>$reservation
        ]);

    }

    public function destroy($id)
    {
        $reservation = Reservation::find($id);

        if(is_null($reservation))
        {
            return response()->json([
                'status'=>false,
                'message'=>'Reservation not found',
                'data'=>null
            ]);
        }else{
            $reservation->delete();
            return response()->json([
                'status'=>true,
                'message'=>'Reservation deleted success fully',
                'data'=>null
            ]);
        }
    }
}
