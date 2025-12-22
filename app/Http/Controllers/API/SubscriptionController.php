<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Plan;
use Carbon\Carbon;
use App\Mail\ClientSubscription;
use Mail;


class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $user = auth()->user();
        $subscriptions = $user->subscriptions()->with('plan')->orderBy('created_at','desc')->get();
        return response()->json([
            'status'=>true,
            'message'=>'User Subscription',
            'datas'=>$subscriptions
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $start_date = $this->getStartDate($user);
        $plan = Plan::find($request->plan_id);
        $duration = $plan->days;
        $end_date = $this->getEndDate($start_date,$duration);

        $subscription = new Subscription();
        $subscription->plan_id = $request->plan_id;
        $subscription->user_id = $user->id;
        $subscription->start_date = $start_date;
        $subscription->end_date = $end_date;
        $subscription->price = $plan->price;
        $subscription->status = 'pending';
        $subscription->save();

        $subscription = Subscription::with('user','plan')->find($subscription->id)->toArray();

        Mail::to([$user->email,'bob.fleifel@gmail.com'])->send(new ClientSubscription($subscription));

        return response()->json([
            'status'=>true,
            'message'=>'User Subscription created successfully',
            'data'=>$subscription
        ]);   
    }

    public function getStartDate($user)
    {
        $subscriptions = $user->subscriptions()->with('plan')->orderBy('created_at','desc')->get();
        if(!is_null($subscriptions) && $subscriptions->count() > 0)
        {
            $latestSubscription = $subscriptions[0];
            $today = now();
    
            if ($latestSubscription->end_date->lessThan($today)) {
                return $today;
            } else {
                return $latestSubscription->end_date->addDay();
            }
        }
        // Return today's date if there are no subscriptions
        return now();
    }

    public function getEndDate($start_date, $duration)
    {
        // Convert the start_date to a Carbon instance
        $startDate = Carbon::parse($start_date);
    
        // Calculate the end date by adding the duration to the start date
        $endDate = $startDate->copy()->addDays($duration);
    
        return $endDate;
    }
}
