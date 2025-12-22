<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Http;
use Log;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $title = '';
    public $body = '';
    public $device_id = '';
    public $task = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($device_id,$title,$body,$task)
    {
        $this->device_id = $device_id;
        $this->title = $title;
        $this->body = $body;
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = 'https://fcm.googleapis.com/v1/projects/myproject-b5ae1/messages:send';
        $serverKey = env("FirebaseKey");
        
        $data = [
            "registration_ids" => [$this->device_id],
            "notification" => [
                "title" => $this->title,
                "body" => $this->body,  
            ],
            "data"=>[
                'type'=>'task',
                'id'=>$this->task->id,
                'data'=>$this->task->toArray()
            ]
        ];
        
        $headers = [
            'Authorization' => 'Bearer' . $serverKey,
            'Content-Type' => 'application/json',
        ];
        
        $response = Http::withHeaders($headers)->post($url, $data);
        
        if ($response->successful()) {
            // The request went through
            $result = $response->body();
        } else {
            // There was an error with the request
            $result = $response->serverError() ? 'Server error' : 'Client error';
            $result .= ': ' . $response->body();
        }
        
        // You can dump the result to the screen
        Log::info(json_encode($result));        
    }
}
