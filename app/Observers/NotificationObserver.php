<?php

namespace App\Observers;

use App\Models\Notification;
use Google\Auth\ApplicationDefaultCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationObserver
{
    /**
     * Handle the Notification "created" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function created(Notification $notification)
    {
        Log::info("inside created");


         // specify the path to your application credentials
         putenv('GOOGLE_APPLICATION_CREDENTIALS=' . base_path('firebase_key.json'));
        
         // define the scopes for your API call
         $scopes = ['https://www.googleapis.com/auth/firebase.messaging', 'https://www.googleapis.com/auth/cloud-platform'];
 
         // create middleware
         $middleware = ApplicationDefaultCredentials::getMiddleware($scopes);
         $stack = HandlerStack::create();
         $stack->push($middleware);
 
         // create the HTTP client
         $client = new Client([
             'handler' => $stack,
             'base_uri' => 'https://www.googleapis.com',
             'auth' => 'google_auth'  // authorize all requests
         ]);
 
         // make the request to get the access token
         $credentials = ApplicationDefaultCredentials::getCredentials($scopes);
         $accessToken = $credentials->fetchAuthToken()['access_token'];
 
         // Log the access token
         Log::info('Access Token: ' . $accessToken);
 
         // Define the FCM API endpoint
         $url = 'https://fcm.googleapis.com/v1/projects/threesixty-97364/messages:send';
 
         // Create the payload for the FCM request
         if($notification->user->devices->pluck('device_id')->count()>0)
         {


            $data = [
                "message" => [
                    "token" => $notification->user->devices->pluck('device_id')->toArray()[0],
                    "notification" => [
                        "title" => $notification->title,
                        "body" => $notification->message
                    ]
                ]
            ];
   
            Log::info($data);
    
            // Define the headers for the FCM request
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
    
            // Send the request to FCM
            $response = Http::withHeaders($headers)->post($url, $data);
    
            // Handle the response
            if ($response->successful()) {
                // The request went through
                $result = $response->body();
            } else {
                // There was an error with the request
                $result = $response->serverError() ? 'Server error' : 'Client error';
                $result .= ': ' . $response->body();
    
                // Log the error for debugging
                Log::error('FCM Request Error: ' . $result);
            }
    
            // Log the result
            Log::info(json_encode($result));


         }

    }

    /**
     * Handle the Notification "updated" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function updated(Notification $notification)
    {
        //
    }

    /**
     * Handle the Notification "deleted" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function deleted(Notification $notification)
    {
        //
    }

    /**
     * Handle the Notification "restored" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function restored(Notification $notification)
    {
        //
    }

    /**
     * Handle the Notification "force deleted" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function forceDeleted(Notification $notification)
    {
        //
    }
}
