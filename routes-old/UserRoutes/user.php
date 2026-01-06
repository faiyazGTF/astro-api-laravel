<?php

use App\Http\Controllers\ChatAndCallController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\User\AstroReviewController;
use App\Http\Controllers\User\GiftController;
use App\Http\Controllers\User\HomePageController;
use App\Http\Controllers\User\RechargePackageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;
use Illuminate\Http\Request;
// 'middleware' => 'api.auth'
Route::group(['prefix' => 'user'], function () {

 
    Route::get('app-version', [UserController::class, 'AppVersion']);
 

    Route::group(['prefix' => 'profile','middleware' => 'api.auth'],function () {
        Route::post('refresh-token', [UserController::class, 'refreshToken']);
        Route::post('delete', [UserController::class, 'deleteAccount']);
        Route::get('/{id}', [UserController::class, 'getProfile']);
        Route::post('update-profile-image', [UserController::class, 'updateProfileImage']);
        Route::post('update-profile', [UserController::class, 'UpdateProfile']);
        Route::post('follow-astro', [UserController::class, 'FollowAstrologer']);
        Route::post('default-profile', [UserController::class, 'defaultProfile']);

    });
    Route::post('similar-astro', [HomePageController::class, 'SimilarAstrologer']);

    Route::group(['middleware' => 'api.auth'],function () {
        // review 
        Route::post('comment-on-astroProfile', [AstroReviewController::class, 'add']);
        Route::get('astro-review/{astroid}', [AstroReviewController::class, 'getAstroReview']);
        Route::post('rating-on-astroProfile', [AstroReviewController::class, 'Add_rating']);
        Route::post('is-anonymous', [AstroReviewController::class, 'is_anonymous']);
        Route::post('life-timerate', [AstroReviewController::class, 'UserOnetimerate']);
        // end review 
    });

    Route::get('homepage-blogs', [HomePageController::class, 'getBlogs']);
    
    Route::post('send-feedback-to-ceo', [HomePageController::class, 'sendFeedbackToCeo']);
    Route::get('my-following/{user_id}', [UserController::class, 'myFollowing']);




    Route::get('blog/{id}', [HomePageController::class, 'getBlogDetails']);

    Route::group(['prefix' => 'recharge'],function () {
        Route::get('/list', [RechargePackageController::class, 'RechageList'])->middleware('api.auth');
        Route::post('/custom', [RechargePackageController::class, 'CustomeRecharge']);
        Route::post('/razorpay-webhook-response', [RechargePackageController::class, 'RazorpayWebhooks']);
        Route::post('/{plaind}', [RechargePackageController::class, 'RechargeByPlan']);
		Route::get('/plan/{plaind}', [RechargePackageController::class, 'getSinglePlan']);
		Route::post('/checkout-recharge/details', [RechargePackageController::class, 'CheckoutRecharge']);

    });

    Route::group(['middleware' => 'api.auth'],function () {
        Route::group(['prefix' => 'gift'],function () {
            Route::get('/', [GiftController::class, 'index']);
            Route::get('/{giftId}', [GiftController::class, 'giftDetails']);
            Route::post('share/{giftId}', [GiftController::class, 'shareGift']);
        });
        Route::post('/change-preffered-langage', [UserController::class, 'ChangePrefferedLanguage']);
        Route::post('save-device-token', [UserController::class, 'SaveDeviceToken']);
            Route::get('get-ongoing-session', [ChatAndCallController::class, 'getOngoingSession']);
    });
    Route::post('customer-support', [UserController::class, 'customerSuppoert']);
    Route::get('faq-list', [FAQController::class, 'index']);
    Route::get('homepage-pooja-list', [HomePageController::class, 'getHomepagePooja']);
    Route::get('transaction-list/{userid}', [RechargePackageController::class, 'getTransactionHistoryByUser']);


    Route::get('/stream-recording', function (Request $request) {
        $recordUrl = $request->query('url');

        if (!$recordUrl) {
            return response('No record URL found', 400);
        }

        $base64Auth = env('BASE64_ENCODED_AUTH');

        $audioResponse = Http::withHeaders([
            'Authorization' => "Basic {$base64Auth}"
        ])->get($recordUrl);

        if ($audioResponse->status() !== 200) {
            return response('Unable to fetch audio file', 404);
        }

        return Response::make($audioResponse->body(), 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="recording.mp3"',
        ]);
    })->name('stream-recording');  



});