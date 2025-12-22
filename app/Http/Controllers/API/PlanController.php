<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
class PlanController extends Controller
{
    public function index(Request $request)
    {
        $plans = Plan::all();
        return response()->json([
            'status'=>true,
            'datas'=>$plans,
            'message'=>'list of plans'
        ]);
    }
}
