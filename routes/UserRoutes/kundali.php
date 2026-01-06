<?php

use App\Http\Controllers\User\FreeKundaliController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;

use Illuminate\Http\Request;

// 'middleware' => 'api.auth'
Route::group(['prefix' => 'user'], function () {

    Route::group(['prefix' => 'kundali'], function () {

        Route::post('save', [FreeKundaliController::class, 'SaveFreeKundli']);
        Route::get('get-saved-kundli/{user_id}', [FreeKundaliController::class, 'getSavedKundli']);

        Route::post('basic-details', [FreeKundaliController::class, 'Basic']);
        Route::post('manglik-analysis', [FreeKundaliController::class, 'MangikAnalysis']);
        Route::post('manglik-analysis', [FreeKundaliController::class, 'MangikAnalysis']);

        Route::post('planet-position', [FreeKundaliController::class, 'PlanetPositions']);


        Route::post('cuspal-chart', [FreeKundaliController::class, 'CuspalChart']);
        Route::post('kp-planet-position', [FreeKundaliController::class, 'KPPlanetPosition']);
        Route::post('ashtakvarga-chart', [FreeKundaliController::class, 'AshtakvargaChart']);



        Route::post('karakansha-chart', [FreeKundaliController::class, 'KarakanshaChart']);
        Route::post('chara-dasha-chart', [FreeKundaliController::class, 'CharaCashaChart']);
        Route::post('padas-table', [FreeKundaliController::class, 'PadasTable']);
        Route::post('shadbala-chart', [FreeKundaliController::class, 'ShadbalaChart']);
        Route::post('dasha', [FreeKundaliController::class, 'Dasha']);
        Route::post('dasha-table', [FreeKundaliController::class, 'DashaTable']);
        Route::post('dahsa-yogini', [FreeKundaliController::class, 'DashaYogini']);
        Route::post('chart-details/{type}', [FreeKundaliController::class, 'Chart']);



        // report 
        Route::post('general-report', function (Request $request) {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|min:3',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',

            ]);
            if ($validator->fails()) {
                $response =  [
                    'statusCode' => 403,
                    'sattus' => false,
                    'error' => $validator->errors()
                ];
            } else {
                $response =  FreeKundaliController::GeneralReport($request->all());
            }


            return response()->json($response);
        });
        Route::post('general-report-yoga', [FreeKundaliController::class, 'GeneralReportYoga']);
        Route::post('general-planet-analysis', [FreeKundaliController::class, 'GeneralPlanetAnalysis']);



        Route::post('gemstone', [FreeKundaliController::class, 'Gemstone']);
        Route::post('sadesati', [FreeKundaliController::class, 'Sadesati']);


        Route::post('/varshaphal', [FreeKundaliController::class, 'varshaphal']);
        Route::post('kaalsarp-report', [FreeKundaliController::class, 'KaalsarpReport']);




        // end report 



    });
});
