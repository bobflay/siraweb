<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
class PostController extends Controller
{

    public function index()
    {
        $posts = Post::with('photos')->whereStatus('published')->get()->all();
        return response()->json([
            'status'=>true,
            'message'=>'List of posts',
            'datas'=>$posts
        ]);
    }
}
