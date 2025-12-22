<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cafe;

class CafeController extends Controller
{
    public function index()
    {
        // dd(auth()->user()->groups->toArray());
        $group = auth()->user()->groups->first();
        $cafe = Cafe::orderByDesc('created_at')->limit(1)->get()->first();
        return response()->json([
            'status'=>true,
            'message'=>'Cafe Menu',
            'data'=>$group
        ]);
    }
}
