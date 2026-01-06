<?php

use App\Http\Controllers\User\MatchingKundliController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController as UserUserController;

Route::group(['prefix' => 'user'], function () {
    Route::group(['prefix' => 'matching-kundli'], function () {
        Route::post('dosha', [MatchingKundliController::class, 'dosha']);
        Route::post('basic-details', [MatchingKundliController::class, 'basic']);
        Route::post('ashtakoot', [MatchingKundliController::class, 'ashtakoot']);
        Route::post('horoscope-chart/moon-chart-p1-p2', [MatchingKundliController::class, 'getMoonChartP1P2']);
        Route::post('horoscope-chart/{type}', [MatchingKundliController::class, 'horoscopeChart']);
    });
});
