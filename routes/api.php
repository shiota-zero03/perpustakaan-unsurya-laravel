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

Route::controller(\App\Http\Controllers\Api\PublicController::class)->group(function(){
    Route::post('/visitor', 'visitor_store');
});

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/profile', [\App\Http\Controllers\Api\AuthController::class, 'get_profile']);
    Route::post('/auth/sign-out', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

    Route::controller(\App\Http\Controllers\Api\DosenController::class)->prefix('dosen')->group(function(){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::get('/sample/export', 'sample_export');
        Route::get('/data/export', 'dosen_export');
        Route::post('/data/import', 'dosen_import');
        Route::post('/store', 'store');
        Route::post('/action-selected', 'selected_action');
        Route::put('/update/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(\App\Http\Controllers\Api\MahasiswaController::class)->prefix('mahasiswa')->group(function(){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::get('/sample/export', 'sample_export');
        Route::get('/data/export', 'mahasiswa_export');
        Route::post('/data/import', 'mahasiswa_import');
        Route::post('/store', 'store');
        Route::post('/action-selected', 'selected_action');
        Route::put('/update/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(\App\Http\Controllers\Api\PetugasController::class)->prefix('petugas')->group(function(){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/store', 'store');
        Route::post('/action-selected', 'selected_action');
        Route::put('/update/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(\App\Http\Controllers\Api\BukuFisikController::class)->prefix('buku-fisik')->group(function(){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::get('/sample/export', 'sample_export');
        Route::get('/data/export', 'buku_fisik_export');
        Route::post('/data/import', 'buku_fisik_import');
        Route::post('/store', 'store');
        Route::post('/action-selected', 'selected_action');
        Route::put('/update/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(\App\Http\Controllers\Api\BukuDigitalController::class)->prefix('buku-digital')->group(function(){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::get('/sample/export', 'sample_export');
        Route::get('/data/export', 'buku_digital_export');
        Route::post('/data/import', 'buku_digital_import');
        Route::post('/store', 'store');
        Route::post('/action-selected', 'selected_action');
        Route::put('/update/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(\App\Http\Controllers\Api\VisitorController::class)->group(function(){
        Route::get('/visitor', 'index');
    });

    Route::controller(\App\Http\Controllers\Api\FakultasController::class)->prefix('fakultas')->group(function(){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/store', 'store');
        Route::put('/update/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(\App\Http\Controllers\Api\ProdiController::class)->prefix('prodi')->group(function(){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/store', 'store');
        Route::put('/update/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(\App\Http\Controllers\Api\OptionController::class)->prefix('option')->group(function(){
        Route::get('/faculty', 'faculty');
        Route::get('/department', 'prodi');
    });

    Route::controller(\App\Http\Controllers\Api\BannerController::class)->prefix('banner')->group(function(){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/store', 'store');
        Route::put('/update/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(\App\Http\Controllers\Api\NewsController::class)->prefix('news')->group(function(){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/store', 'store');
        Route::put('/update/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(\App\Http\Controllers\Api\SettingController::class)->prefix('setting')->group(function(){
        Route::get('/', 'index');
        Route::put('/profil', 'profil');
        Route::put('/petunjuk', 'petunjuk');
        Route::put('/prosedur', 'prosedur');
    });
});

Route::controller(\App\Http\Controllers\Api\LandingPageController::class)->prefix('landing-page')->group(function(){
    Route::get('/banner', 'banner');
    Route::get('/profil', 'profil');
    Route::get('/petunjuk', 'petunjuk');
    Route::get('/prosedur', 'prosedur');
    Route::get('/news', 'news');
    Route::get('/news/{slug}', 'news_detail');
});
