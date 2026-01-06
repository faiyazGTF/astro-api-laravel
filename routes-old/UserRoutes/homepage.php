<?php

use App\Http\Controllers\User\AstroReviewController;
use App\Http\Controllers\User\HomePageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController as UserUserController;
// 'middleware' => 'api.auth'

Route::group(['prefix' => 'user'], function () {
    Route::get('home-banner', [HomePageController::class, 'getHomePageBanners']);
    Route::get('homepage-astrologer', [HomePageController::class, 'getHomepageAstrologer']);
	Route::get('homepage-astrologer-online', [HomePageController::class, 'getOnlineAstrologers']);
    Route::get('homepage-astrologer-verified', [HomePageController::class, 'getVerifiedAstrologers']);
    Route::get('homepage-astrologer-trending', [HomePageController::class, 'getTrendingAstrologers']);


});
