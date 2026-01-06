<?php

namespace App\Models\Commons;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Stmt\TryCatch;

class Horoscope extends Model
{
    use HasFactory;
    protected $table='horoscope';
    public static function getList(){
       try {
        $result=Horoscope::get();
        return response()->json([
            'statusCode'=>200,
            'status'=>true,
            'message'=>'success',
            'data'=>$result
        ]);
       } catch (\Throwable $th) {
        return response()->json([
            'statusCode'=>500,
            'status'=>false,
            'message'=>$th->getMessage()
        ]);
       }
    }
    public static function getHoroscopeData($type,$sign){
        try {
            $get_daily_horoscope = null;
            $date = date('Y-m-d');
            if ($type == 'daily' || $type == 'yesterday' || $type == 'tomorrow') {
          
                if ($type == 'yesterday') {
                    $date = date('Y-m-d', strtotime('-1 days'));
                } elseif ($type == 'tomorrow') {
                    $date = date('Y-m-d', strtotime('+1 days'));
                }
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://divineapi.com/api/1.0/get_daily_horoscope.php',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => ['lan'=>'hi','date' => $date, 'sign' => $sign, 'api_key' => '996009f2374006606f4c0b0fda878af1', 'timezone' => '5.5'],
                ]);
                $get_daily_horoscope = curl_exec($curl);
                curl_close($curl);
            }
            if ($type == 'weekly') {
                $week = 'current'; //date('m');
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://divineapi.com/api/1.0/get_weekly_horoscope.php',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => ['week' => $week, 'sign' => $sign, 'api_key' => '996009f2374006606f4c0b0fda878af1', 'timezone' => '5.5'],
                ]);
                $get_daily_horoscope = curl_exec($curl);
                curl_close($curl);
            }
            if ($type == 'monthly') {
                $month = 'current'; //date('m');
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://divineapi.com/api/1.0/get_monthly_horoscope.php',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => ['month' => $month, 'sign' => $sign, 'api_key' => '996009f2374006606f4c0b0fda878af1', 'timezone' => '5.5'],
                ]);
                $get_daily_horoscope = curl_exec($curl);
                curl_close($curl);
            }
            if ($type == 'yearly') {
                $year = 'current';
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://divineapi.com/api/1.0/get_yearly_horoscope.php',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => ['year' => $year, 'sign' => $sign, 'api_key' => '996009f2374006606f4c0b0fda878af1', 'timezone' => '5.5'],
                ]);
                $get_daily_horoscope = curl_exec($curl);
                curl_close($curl);
            }
    
            $checkdata = json_decode($get_daily_horoscope);
            return response()->json([
                'statusCode'=>200,
                'status'=>true,
                'message'=>'success',
                'data'=>$checkdata
            ]);
        } catch (\Throwable $er) {
            return response()->json([
                'statusCode'=>500,
                'status'=>false,
                'message'=>$er->getMessage()
            ]);
        }
    } 
}
