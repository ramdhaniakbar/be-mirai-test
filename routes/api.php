<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'API is running';
});

// public routes (no-token)
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::get('/user-activity', [UserActivityController::class, 'user_activity'])->name('user.activity');

// protected routes (using-token)
Route::middleware('auth:sanctum')->group(function () {
    // user routes
    Route::get('/user', [UserController::class, 'user_profile'])->name('user.profile');
    Route::put('/user', [UserController::class, 'update_profile'])->name('user.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // diaries routes
    Route::apiResource('/diaries', DiaryController::class);
});