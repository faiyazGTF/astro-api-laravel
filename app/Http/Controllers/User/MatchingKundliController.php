<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class MatchingKundliController extends Controller
{
    //

    public function dosha(Request $request){
            $validator = Validator::make($request->all(), [
                'p1_full_name' => 'required',
                'p1_birth_date' => 'required|date',
                'p1_birth_time' => 'required|date_format:H:i:s',
                'p1_gender' => 'required|in:male,female',
                'p1_birth_place' => 'required',
                'p1_latitude' => 'required',
                'p1_longitude' => 'required',
                'p1_time_zone' => 'required',
                'p2_full_name' => 'required',
                'p2_birth_date' => 'required|date',
                'p2_birth_time' => 'required|date_format:H:i:s',
                'p2_gender' => 'required|in:male,female',
                'p2_birth_place' => 'required',
                'p2_latitude' => 'required',
                'p2_longitude' => 'required',
                'p2_time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $requestdata=[
                'api_key'=>env('DIVINE_KEY'),
                'p1_full_name'=>$request->p1_full_name,
                'p1_day'=>Carbon::parse($request->p1_birth_date)->format('d'),
                'p1_month'=>Carbon::parse($request->p1_birth_date)->format('m'),
                'p1_year'=>Carbon::parse($request->p1_birth_date)->format('Y'),
                'p1_hour'=>Carbon::parse($request->p1_birth_time)->format('H'),
                'p1_min'=>Carbon::parse($request->p1_birth_time)->format('i'),
                'p1_sec'=>Carbon::parse($request->p1_birth_time)->format('s'),
                'p1_gender'=>$request->p1_gender,
                'p1_place'=>$request->p1_birth_place,
                'p1_lat'=>$request->p1_latitude,
                'p1_lon'=>$request->p1_longitude,
                'p1_tzone'=>$request->p1_time_zone,
                'p2_full_name'=>$request->p2_full_name,
                'p2_day'=>Carbon::parse($request->p2_birth_date)->format('d'),
                'p2_month'=>Carbon::parse($request->p2_birth_date)->format('m'),
                'p2_year'=>Carbon::parse($request->p2_birth_date)->format('Y'),
                'p2_hour'=>Carbon::parse($request->p2_birth_time)->format('H'),
                'p2_min'=>Carbon::parse($request->p2_birth_time)->format('i'),
                'p2_sec'=>Carbon::parse($request->p2_birth_time)->format('s'),
                'p2_gender'=>$request->p2_gender,
                'p2_place'=>$request->p2_birth_place,
                'p2_lat'=>$request->p2_latitude,
                'p2_lon'=>$request->p2_longitude,
                'p2_tzone'=>$request->p2_time_zone,
                'lan'=>!empty($request->lang) ? $request->lang  : 'en',
            ];  

            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/matching/manglik-dosha';
            $result= guzzleRequestPost($url,$requestdata);
           
            if($result['success']==1){
             
                return ApiResponse(200,true,'success',$result);
            }

            return InternalError('diviene api not responsed');

    } 

    public function  basic(Request $request){
        try {
           
        $validator = Validator::make($request->all(), [
            'p1_full_name' => 'required',
            'p1_birth_date' => 'required|date',
            'p1_birth_time' => 'required|date_format:H:i:s',
            'p1_gender' => 'required|in:male,female',
            'p1_birth_place' => 'required',
            'p1_latitude' => 'required',
            'p1_longitude' => 'required',
            'p1_time_zone' => 'required',
            'p2_full_name' => 'required',
            'p2_birth_date' => 'required|date',
            'p2_birth_time' => 'required|date_format:H:i:s',
            'p2_gender' => 'required|in:male,female',
            'p2_birth_place' => 'required',
            'p2_latitude' => 'required',
            'p2_longitude' => 'required',
            'p2_time_zone' => 'required',
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        

        $requestdata=[
            'api_key'=>env('DIVINE_KEY'),
            'p1_full_name'=>$request->p1_full_name,
            'p1_day'=>Carbon::parse($request->p1_birth_date)->format('d'),
            'p1_month'=>Carbon::parse($request->p1_birth_date)->format('m'),
            'p1_year'=>Carbon::parse($request->p1_birth_date)->format('Y'),
            'p1_hour'=>Carbon::parse($request->p1_birth_time)->format('H'),
            'p1_min'=>Carbon::parse($request->p1_birth_time)->format('i'),
            'p1_sec'=>Carbon::parse($request->p1_birth_time)->format('s'),
            'p1_gender'=>$request->p1_gender,
            'p1_place'=>$request->p1_birth_place,
            'p1_lat'=>$request->p1_latitude,
            'p1_lon'=>$request->p1_longitude,
            'p1_tzone'=>$request->p1_time_zone,
            'p2_full_name'=>$request->p2_full_name,
            'p2_day'=>Carbon::parse($request->p2_birth_date)->format('d'),
            'p2_month'=>Carbon::parse($request->p2_birth_date)->format('m'),
            'p2_year'=>Carbon::parse($request->p2_birth_date)->format('Y'),
            'p2_hour'=>Carbon::parse($request->p2_birth_time)->format('H'),
            'p2_min'=>Carbon::parse($request->p2_birth_time)->format('i'),
            'p2_sec'=>Carbon::parse($request->p2_birth_time)->format('s'),
            'p2_gender'=>$request->p2_gender,
            'p2_place'=>$request->p2_birth_place,
            'p2_lat'=>$request->p2_latitude,
            'p2_lon'=>$request->p2_longitude,
            'p2_tzone'=>$request->p2_time_zone,
            'lan'=>!empty($request->lang) ? $request->lang  : 'en',
        ];

    $url = 'https://astroapi-3.divineapi.com/indian-api/v2/matching/basic-astro-details';
    $result= guzzleRequestPost($url,$requestdata);


    $result= guzzleRequestPost($url,$requestdata);
       
    if($result['success']==1){
     
        return ApiResponse(200,true,'success',$result);
    }

        return InternalError('diviene api not responsed');
        } catch (\Throwable $th) {
            return InternalError( $th->getMessage());
        }

    }

    

    public function ashtakoot(Request $request){
        
        try {
            $validator = Validator::make($request->all(), [
                'p1_full_name' => 'required',
                'p1_birth_date' => 'required|date',
                'p1_birth_time' => 'required|date_format:H:i:s',
                'p1_gender' => 'required|in:male,female',
                'p1_birth_place' => 'required',
                'p1_latitude' => 'required',
                'p1_longitude' => 'required',
                'p1_time_zone' => 'required',
                'p2_full_name' => 'required',
                'p2_birth_date' => 'required|date',
                'p2_birth_time' => 'required|date_format:H:i:s',
                'p2_gender' => 'required|in:male,female',
                'p2_birth_place' => 'required',
                'p2_latitude' => 'required',
                'p2_longitude' => 'required',
                'p2_time_zone' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $requestdata=[
                'api_key'=>env('DIVINE_KEY'),
                'p1_full_name'=>$request->p1_full_name,
                'p1_day'=>Carbon::parse($request->p1_birth_date)->format('d'),
                'p1_month'=>Carbon::parse($request->p1_birth_date)->format('m'),
                'p1_year'=>Carbon::parse($request->p1_birth_date)->format('Y'),
                'p1_hour'=>Carbon::parse($request->p1_birth_time)->format('H'),
                'p1_min'=>Carbon::parse($request->p1_birth_time)->format('i'),
                'p1_sec'=>Carbon::parse($request->p1_birth_time)->format('s'),
                'p1_gender'=>$request->p1_gender,
                'p1_place'=>$request->p1_birth_place,
                'p1_lat'=>$request->p1_latitude,
                'p1_lon'=>$request->p1_longitude,
                'p1_tzone'=>$request->p1_time_zone,
                'p2_full_name'=>$request->p2_full_name,
                'p2_day'=>Carbon::parse($request->p2_birth_date)->format('d'),
                'p2_month'=>Carbon::parse($request->p2_birth_date)->format('m'),
                'p2_year'=>Carbon::parse($request->p2_birth_date)->format('Y'),
                'p2_hour'=>Carbon::parse($request->p2_birth_time)->format('H'),
                'p2_min'=>Carbon::parse($request->p2_birth_time)->format('i'),
                'p2_sec'=>Carbon::parse($request->p2_birth_time)->format('s'),
                'p2_gender'=>$request->p2_gender,
                'p2_place'=>$request->p2_birth_place,
                'p2_lat'=>$request->p2_latitude,
                'p2_lon'=>$request->p2_longitude,
                'p2_tzone'=>$request->p2_time_zone,
                'lan'=>!empty($request->lang) ? $request->lang  : 'en',
            ];  
    
            $url = 'https://astroapi-3.divineapi.com/indian-api/v2/ashtakoot-milan';
            $result= guzzleRequestPost($url,$requestdata);
           
            if($result['success']==1){
             
                return ApiResponse(200,true,'success',$result);
            }
    
            return InternalError('diviene api not responsed');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());

        }
    } 

    public function horoscopeChart(Request $request,$type){
        
        try {
            $validator = Validator::make($request->all(), [
                'p1_full_name' => 'required',
                'p1_birth_date' => 'required|date',
                'p1_birth_time' => 'required|date_format:H:i:s',
                'p1_gender' => 'required|in:male,female',
                'p1_birth_place' => 'required',
                'p1_latitude' => 'required',
                'p1_longitude' => 'required',
                'p1_time_zone' => 'required',
                'p2_full_name' => 'required',
                'p2_birth_date' => 'required|date',
                'p2_birth_time' => 'required|date_format:H:i:s',
                'p2_gender' => 'required|in:male,female',
                'p2_birth_place' => 'required',
                'p2_latitude' => 'required',
                'p2_longitude' => 'required',
                'p2_time_zone' => 'required',
       
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $requestdata=[
                'api_key'=>env('DIVINE_KEY'),
                'p1_full_name'=>$request->p1_full_name,
                'p1_day'=>Carbon::parse($request->p1_birth_date)->format('d'),
                'p1_month'=>Carbon::parse($request->p1_birth_date)->format('m'),
                'p1_year'=>Carbon::parse($request->p1_birth_date)->format('Y'),
                'p1_hour'=>Carbon::parse($request->p1_birth_time)->format('H'),
                'p1_min'=>Carbon::parse($request->p1_birth_time)->format('i'),
                'p1_sec'=>Carbon::parse($request->p1_birth_time)->format('s'),
                'p1_gender'=>$request->p1_gender,
                'p1_place'=>$request->p1_birth_place,
                'p1_lat'=>$request->p1_latitude,
                'p1_lon'=>$request->p1_longitude,
                'p1_tzone'=>$request->p1_time_zone,
                'p2_full_name'=>$request->p2_full_name,
                'p2_day'=>Carbon::parse($request->p2_birth_date)->format('d'),
                'p2_month'=>Carbon::parse($request->p2_birth_date)->format('m'),
                'p2_year'=>Carbon::parse($request->p2_birth_date)->format('Y'),
                'p2_hour'=>Carbon::parse($request->p2_birth_time)->format('H'),
                'p2_min'=>Carbon::parse($request->p2_birth_time)->format('i'),
                'p2_sec'=>Carbon::parse($request->p2_birth_time)->format('s'),
                'p2_gender'=>$request->p2_gender,
                'p2_place'=>$request->p2_birth_place,
                'p2_lat'=>$request->p2_latitude,
                'p2_lon'=>$request->p2_longitude,
                'p2_tzone'=>$request->p2_time_zone,
                'lan'=>!empty($request->lang) ? $request->lang  : 'en',
            ];  
    
            $url = 'https://astroapi-3.divineapi.com/indian-api/v1/matching/horoscope-chart/'.$type;
            $result= guzzleRequestPost($url,$requestdata);
           
            if($result['success']==1){
             
                return ApiResponse(200,true,'success',$result);
            }
    
            return InternalError('diviene api not responsed');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());

        }
    } 

    
}
