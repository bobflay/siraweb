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
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\InvoiceOcrController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\StockController;

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

    // Homepage endpoint
    Route::get('home', [HomeController::class, 'index']);

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
    Route::get('visits/active', [VisitController::class, 'active']);
    Route::get('visits/distance-exceed-reasons', [VisitController::class, 'distanceExceedReasons']);
    Route::post('visits', [VisitController::class, 'store']);
    Route::post('visits/{visit}/terminate', [VisitController::class, 'terminate']);
    Route::delete('visits/{visit}', [VisitController::class, 'destroy']);

    // Visit report endpoints
    Route::get('clients/{client}/reports', [VisitReportController::class, 'indexByClient']);
    Route::post('visits/{visit}/report', [VisitReportController::class, 'store']);
    Route::get('visits/{visit}/report', [VisitReportController::class, 'show']);
    Route::delete('visits/{visit}/report/photos/{photo}', [VisitReportController::class, 'deletePhoto']);

    // Invoice OCR endpoints
    Route::post('ocr/invoice', [InvoiceOcrController::class, 'processImage']);
    Route::post('photos/{photo}/ocr', [InvoiceOcrController::class, 'processPhoto']);
    Route::get('photos/{photo}/ocr', [InvoiceOcrController::class, 'getOcrResult']);
    Route::delete('ocr/cache', [InvoiceOcrController::class, 'clearCache']);

    // Invoice CRUD endpoints
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::post('invoices', [InvoiceController::class, 'store']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy']);
    Route::put('invoices/{invoice}/deliver', [InvoiceController::class, 'deliver']);

    // Order endpoints (informational only - no stock impact)
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);

    // Stock Commercial endpoints
    Route::get('stock', [StockController::class, 'index']);
    Route::get('stock/movements', [StockController::class, 'movements']);

    // Product endpoints
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/categories', [ProductController::class, 'categories']);
    Route::get('products/{product}', [ProductController::class, 'show']);
});





