<?php

use App\Http\Controllers\ChatAndCallController;
use App\Http\Controllers\User\AstrologerController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], function () {
   
    Route::group(['prefix' => 'chat'],function () {
        Route::get('astro-list/{type?}', [AstrologerController::class, 'getChatAstroList']);
        Route::get('astro/{astroId}', [AstrologerController::class, 'getAstroProfile']);
        Route::post('/', [ChatAndCallController::class, 'CheckAvability']);
        Route::post('start', [ChatAndCallController::class, 'StartChat']);
        Route::post('end', [ChatAndCallController::class, 'endChat']);
        Route::post('save', [ChatAndCallController::class, 'save_chat']);
        Route::post('change-message-status', [ChatAndCallController::class, 'ChangeMessageStatus']);
        Route::get('consult-history/{user_id}',[ChatAndCallController::class, 'ConsultHistory']);
        Route::get('consult-history-home/{user_id}',[ChatAndCallController::class, 'ConsultHistoryhome']);
		Route::get('consult-history-astrologer/{user_id}',[ChatAndCallController::class, 'getConsultHistoryAstrologer']);
        Route::get('chat-history/{id}',[ChatAndCallController::class, 'ChatHistory']);
    });

    Route::group(['prefix' => 'call'],function () {
        Route::post('/', [ChatAndCallController::class, 'CheckAvability']);
        Route::post('start', [ChatAndCallController::class, 'StartCalling']);
        Route::post('switch-request', [ChatAndCallController::class, 'SwitchToCallRequest']);
        Route::post('switch-session', [ChatAndCallController::class, 'SwitchToCall']);
    });


    Route::group(['prefix' => 'video'],function () {
        Route::post('/', [ChatAndCallController::class, 'CheckAvability']);
        Route::post('start', [ChatAndCallController::class, 'StartVideoCalling']);
        Route::post('switch-request', [ChatAndCallController::class, 'SwitchToCallRequest']);
        Route::post('switch-session', [ChatAndCallController::class, 'SwitchToCall']);
    });
    Route::get('get-remedies/{consult_id}',[ChatAndCallController::class, 'GetRemedies']);

    Route::post('endsession', [ChatAndCallController::class, 'endSwitchRequest']);
    Route::post('checkflagavaibility', [ChatAndCallController::class, 'checkflagavaibility']);
    Route::post('checkastroserviceflag', [ChatAndCallController::class, 'checAstroService']);
    Route::post('join-waitlist', [ChatAndCallController::class, 'JoinWaitlist']);
	Route::get('get-session-data/{session_id}', [ChatAndCallController::class, 'getSessionData']);

    Route::post('consult-reviews', [ChatAndCallController::class, 'getconsultreview']);


});