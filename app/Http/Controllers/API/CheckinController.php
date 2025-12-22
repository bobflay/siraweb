<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Checkin;

use Carbon\Carbon;

class CheckinController extends Controller
{
    public function checkin(Request $request)
    {
        $user = auth()->user();
        $subscription = Subscription::where('user_id', $user->id)->where('end_date', '>=', Carbon::now())->first();
        if(!is_null($subscription))
        {
            $checkin = new Checkin();
            $checkin->user_id = $user->id;
            $checkin->save();

            return response()->json([
                'status'=>true,
                'message'=>'User Checkin Successfully',
                'data'=>null
            ]);

        }else{
            
            return response()->json([
                'status'=>false,
                'message'=>'Client does not have active subscription or Reservation',
                'data'=>null
            ]);
        }
    }
}
