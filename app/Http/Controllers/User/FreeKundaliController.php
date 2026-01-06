<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Commons\UserKundaliRequestInfo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class FreeKundaliController extends Controller
{
    //
    public function getSavedKundli(Request $request, $user_id)
    {
        try {
            $result = UserKundaliRequestInfo::index($request, $user_id);
            return ApiResponse(200, true, 'success', $result);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function  SaveFreeKundli(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'name' => 'required',
                'birth_date' => 'required|date|date_format:Y-m-d',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'birth_place' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $result = UserKundaliRequestInfo::saveRecod($request);
            return ApiResponse(200, true, 'success', $result);
        } catch (\Throwable $er) {
            return InternalError($er->getMessage());
        }
    }

    public function  Basic(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v2/basic-astro-details';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {

                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 500,
                'status' => false,
                'message' => $result,
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function MangikAnalysis(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/manglik-dosha';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return ApiResponse(404, false, 'no record found');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function Chart(Request $request, $type)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
                'chart_type' => 'required|in:south,north',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'statusCode' => 403,
                    'status' => false,
                    'message' => 'Please Fill Mandatory fields',
                    'errors' => $validator->errors()
                ]);
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
                'chart_type' => $request->chart_type,

            ];
            
            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/horoscope-chart/' . $type;
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function  PlanetPositions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
                'chart_type' => 'required|in:south,north',

            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
                'chart_type' => $request->chart_type,

            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/planetary-positions';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function CuspalChart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
                'chart_type' => $request->chart_type,

            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/kp/cuspal';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function AshtakvargaChart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
                'chart_type' => $request->chart_type,

            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/bhinnashtakvarga/ashtakvarga';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                $result['data']['description'] = 'Ashakvarga is a technique for assessing a birth charts.';
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function KPPlanetPosition(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
                'chart_type' => $request->chart_type,

            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/kp/planetary-positions';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return response()->json([
                    'statusCode' => 200,
                    'status' => true,
                    'message' => 'success',
                    'data' => $result
                ]);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function KarakanshaChart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
                'chart_type' => $request->chart_type,

            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/jaimini-astrology/karakamsha-lagna';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return response()->json([
                    'statusCode' => 200,
                    'status' => true,
                    'message' => 'success',
                    'data' => $result
                ]);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function PadasTable(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',

            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/jaimini-astrology/padas';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return response()->json([
                    'statusCode' => 200,
                    'status' => true,
                    'message' => 'success',
                    'data' => $result
                ]);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }


    public function CharaCashaChart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',

            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/jaimini-astrology/chara-dasha';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function ShadbalaChart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',

            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/shadbala';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function Dasha(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
                'maha_dasha' => 'required',

            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
                'maha_dasha' => $request->maha_dasha


            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/maha-dasha-analysis';
            $result = guzzleRequestPost($url, $requestdata);
            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function DashaTable(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
                'dasha_type' => 'required',

            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                // 'lan'=>!empty($request->lang) ? $request->lang  : 'en',
                'dasha_type' => $request->dasha_type


            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/vimshottari-dasha';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function DashaYogini(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',

            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',


            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v2/yogini-dasha';
            $result = guzzleRequestPost($url, $requestdata);
            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public static function  GeneralReport($request)
    {
        try {

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request['full_name'],
                'day' => Carbon::parse($request['birth_date'])->format('d'),
                'month' => Carbon::parse($request['birth_date'])->format('m'),
                'year' => Carbon::parse($request['birth_date'])->format('Y'),
                'hour' => Carbon::parse($request['birth_time'])->format('H'),
                'min' => Carbon::parse($request['birth_time'])->format('i'),
                'sec' => Carbon::parse($request['birth_time'])->format('s'),
                'gender' => $request['gender'],
                'place' => $request['place'],
                'lat' => $request['lat'],
                'lon' => $request['long'],
                'tzone' => $request['time_zone'],
                'lan' => !empty($request['lang']) ? $request['lang']  : 'en',
            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v2/ascendant-report';
            $result = guzzleRequestPost($url, $requestdata);
            if (!empty($result) && $result['success'] == 1) {

                return [
                    'statusCode' => 200,
                    'status' => true,
                    'message' => 'success',
                    'data' => $result['data']
                ];
            }
            return  [
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ];
        } catch (\Throwable $th) {

            return [
                'statusCode' => 500,
                'status' => false,
                'message' => 'something went wrong',
                'errors' => $th->getMessage()
            ];
        }
    }

    public function GeneralReportYoga(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',

            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',


            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/yogas';
            $result = guzzleRequestPost($url, $requestdata);
            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function GeneralPlanetAnalysis(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
                'analysis_planet' => 'required'

            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'analysis_planet' => $request->analysis_planet,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',


            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/planet-analysis';
            $result = guzzleRequestPost($url, $requestdata);
            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function Gemstone(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',

            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',


            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v2/gemstone-suggestion';
            $result = guzzleRequestPost($url, $requestdata);
            if ($result['success'] == 1) {
                $result['data']['lucky_stone']['description'] = 'A life stone is a gem for the lagna';
                $result['data']['life_stone']['description'] = 'A Luckey stone is a gem for the lagna';

                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }


    public function Sadesati(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',

            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',


            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/sadhe-sati';
            $result = guzzleRequestPost($url, $requestdata);
            if ($result['success'] == 1) {
                $result['data']['lucky_stone']['description'] = 'A life stone is a gem for the lagna';
                $result['data']['life_stone']['description'] = 'A Luckey stone is a gem for the lagna';

                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }



    public function varshaphal(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',
                'varshaphal_year' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',
                'varshaphal_year' => $request->varshaphal_year,
            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/varshaphal/muntha';
            $result = guzzleRequestPost($url, $requestdata);

            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return ApiResponse(404, false, 'no record found');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function KaalsarpReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'birth_date' => 'required|date',
                'birth_time' => 'required|date_format:H:i:s',
                'gender' => 'required|in:male,female',
                'place' => 'required',
                'lat' => 'required',
                'long' => 'required',
                'time_zone' => 'required',

            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $requestdata = [
                'api_key' => env('DIVINE_KEY'),
                'full_name' => $request->full_name,
                'day' => Carbon::parse($request->birth_date)->format('d'),
                'month' => Carbon::parse($request->birth_date)->format('m'),
                'year' => Carbon::parse($request->birth_date)->format('Y'),
                'hour' => Carbon::parse($request->birth_time)->format('H'),
                'min' => Carbon::parse($request->birth_time)->format('i'),
                'sec' => Carbon::parse($request->birth_time)->format('s'),
                'gender' => $request->gender,
                'place' => $request->place,
                'lat' => $request->lat,
                'lon' => $request->long,
                'tzone' => $request->time_zone,
                'lan' => !empty($request->lang) ? $request->lang  : 'en',


            ];

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/kaal-sarpa-yoga';
            $result = guzzleRequestPost($url, $requestdata);
            if ($result['success'] == 1) {
                return ApiResponse(200, true, 'success', $result);
            }
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'not found',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
}
