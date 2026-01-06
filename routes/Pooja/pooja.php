<?php

use App\Http\Controllers\PoojaController;
use App\Http\Controllers\User\AstrologerController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pooja'], function () {
    Route::get('category', [PoojaController::class, 'getPoojaCategoryList']);
    Route::get('pooja-list', [PoojaController::class, 'getPoojaList']);
    Route::get('{id}', [PoojaController::class, 'getPoojaDetails']);
    Route::post('/', [PoojaController::class, 'BookPooja'])->middleware('api.auth');
    Route::post('webhook-resppnse', [PoojaController::class, 'Webhookresponse']);





});