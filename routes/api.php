<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/', function() { return response()->json(['success' => true, 'message' => 'Api untuk digilib-unsurya.com', "data" => null]); });
Route::prefix('/auth')->controller(\App\Http\Controllers\Api\AuthController::class)->group(function(){
    Route::post('/sign-in', 'sign_in');
    Route::post('/sign-up', 'sign_up');
    Route::post('/forgot-password', 'forgot_password');
    Route::post('/reset-password', 'reset_password');
});

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/profile', [\App\Http\Controllers\Api\AuthController::class, 'get_profile']);
    Route::post('/auth/sign-out', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
});
