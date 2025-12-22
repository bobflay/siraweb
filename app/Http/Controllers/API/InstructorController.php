<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;

class InstructorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $instructors = Instructor::with('reservations')->get()->all();
        return response()->json([
            'status'=>true,
            'message'=>'List of instructors',
            'datas'=>$instructors
        ]);
    }
}
