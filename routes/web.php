<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Mail\NewClientRegistrationAdmin;
use App\Models\User;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('welcome');
});
use App\Models\Reservation;

// routes/web.php
use Illuminate\Support\Facades\Auth;

Auth::routes();

Route::get('/test',function(){
    $user = User::first();
    $reservation = Reservation::with('user')->find(59);
    return view('mails.coachReservation',compact('reservation'));
    // Mail::to('bob.fleifel@gmail.com')->send(new NewClientRegistrationAdmin($user));
    // dd("inn");
    // return view('mails.admins.newClientRegistration',compact('user'));
});
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
