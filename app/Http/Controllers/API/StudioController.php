<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Studio;

class StudioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $studios = Studio::with('images','reservations')->get();
        return response()->json([
            'status'=>true,
            'message'=>'List of studios',
            'datas'=>$studios
        ]);
    }
}