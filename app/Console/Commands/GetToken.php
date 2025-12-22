<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Auth\ApplicationDefaultCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to get an access token and send a test notification via FCM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
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
        $data = [
            "message" => [
                "token" => "dED8BUeDRUOGrLA3Y_FE37:APA91bEhld7_SvGJO9L5gRPZ9r6GiNbkTHFLlFzksZ6NpKteFX0bwBymsa3ioh-R_OYf21j71ztu-xPCmnbpffyzeL_fiumKRZxN0Qasu1o1MwMChqvfaj4si58EVlk8rEvLTgaoN1B9",
                "notification" => [
                    "title" => "test title",
                    "body" => "test body"
                ]
            ]
        ];

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

        return Command::SUCCESS;
    }
}