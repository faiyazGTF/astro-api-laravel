<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    public static function  saveRecord($requestdata){
        $objPayment = new self();
        $objPayment->user_id = $requestdata->user_id;
        $objPayment->order_id = $requestdata->order_id;
        $objPayment->total_amount = $requestdata->total_amount;
        $objPayment->payment_status = $requestdata->payment_status;
        $objPayment->gateway_name = $requestdata->gateway_name;
        $objPayment->currency = $requestdata->currency;
        $objPayment->device_details = $requestdata->device_details;
    
        
        // if($requestdata->gateway_name=='ccavenue'){
        //     $objPayment->razorpay_id = 0;
        //     $objPayment->save();
        //     return true;
    
        // }else{
        //     $razorpayresponse= self::razorpayReques($requestdata->total_amount,$requestdata->order_id);
        //     if(!empty($razorpayresponse->status) && $razorpayresponse->status=='created'){
        //      $razorpay_id = substr($razorpayresponse->id, 6);
        //     }

        //     $objPayment->razorpay_id = @$razorpay_id ? @$razorpay_id : 0;
        //     $objPayment->save();
        //     return $razorpayresponse;
    
        // }


        $razorpayresponse= self::razorpayReques($requestdata->total_amount,$requestdata->order_id);
        if(!empty($razorpayresponse->status) && $razorpayresponse->status=='created'){
         $razorpay_id = substr($razorpayresponse->id, 6);
        }

        $objPayment->razorpay_id = @$razorpay_id ? @$razorpay_id : 0;

        $objPayment->save();

        return $razorpayresponse;

  
 

    }


    public static  function  razorpayReques($amount,$order_id){
        $curl = curl_init();
        $amount = (int) ($amount * 100);
        $auth_header = 'Basic ' . base64_encode(env('RAZORPAY_KEY').':'.env('RAZORPAY_SECRET'));
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.razorpay.com/v1/orders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
          "amount": ' . $amount . ',
          "currency": "INR",
          "receipt": "receipt#1",
          "notes": {
            "key1": "' . $order_id . '",
            "key2": "value2"
          }
        }',
            CURLOPT_HTTPHEADER => array(
                'content-type: application/json',
                'Authorization: '.$auth_header

            ),
        ));

        $resp = curl_exec($curl);
        curl_close($curl);
   
        $response = json_decode($resp);
      
        return $response;
    }
}
