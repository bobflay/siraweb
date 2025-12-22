<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $notifications = auth()->user()->notifications()->orderByDesc('created_at')->limit(30)->get();
        return response()->json([
            'status'=>true,
            'datas'=>$notifications,
            'message'=>'List of notifications'
        ]);
    }
}
