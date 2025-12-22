<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Mail::to("bob.fleifel@gmail.com")->send(new TestMail());
        return Command::SUCCESS;
    }
}
