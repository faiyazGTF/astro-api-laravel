<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController as UserUserController;

Route::group(['prefix' => 'user'], function () {
    Route::post('login', [UserUserController::class, 'login']);
    Route::post('resend-otp', [UserUserController::class, 'resendOTP']);
    Route::post('verify-otp', [UserUserController::class, 'OtpVerify']);    
    Route::post('logout', [UserUserController::class, 'logout'])->middleware('api.auth');

});