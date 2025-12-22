<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DevicesController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\CheckinController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\VisitController;
use App\Http\Controllers\API\VisitReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('verify-otp', [AuthController::class,'verifyOTP']);

Route::get('/posts',[PostController::class,'index']);

Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('password/reset', [ResetPasswordController::class, 'reset']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('request-otp', [AuthController::class,'requestOTP']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('device',[DevicesController::class,'store']);
    Route::get('/notifications',[NotificationController::class,'index']);

    // Client management endpoints
    Route::get('clients', [ClientController::class, 'index']);
    Route::post('clients', [ClientController::class, 'store']);
    Route::put('clients/{client}', [ClientController::class, 'update']);
    Route::post('clients/{client}/photos', [ClientController::class, 'uploadPhotos']);
    Route::delete('clients/{client}/photos/{photo}', [ClientController::class, 'deletePhoto']);

    // Visit management endpoints
    Route::post('visits', [VisitController::class, 'store']);
    Route::post('visits/{visit}/terminate', [VisitController::class, 'terminate']);
    Route::delete('visits/{visit}', [VisitController::class, 'destroy']);

    // Visit report endpoints
    Route::post('visits/{visit}/report', [VisitReportController::class, 'store']);
    Route::get('visits/{visit}/report', [VisitReportController::class, 'show']);
    Route::delete('visits/{visit}/report/photos/{photo}', [VisitReportController::class, 'deletePhoto']);
});





