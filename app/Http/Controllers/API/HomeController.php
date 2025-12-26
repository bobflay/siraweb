<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get homepage data for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get client count using the same scope as the clients API
        $clientCount = Client::forUser($user)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'clients_count' => $clientCount,
                'balance' => 1500000,
            ]
        ]);
    }
}
