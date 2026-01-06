<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    use HasFactory;

    public static function addRecord($data){
        $obj = new self();
            $obj->user_id = $data->user_id;
            $obj->user_kundali_request_info_id = $data->user_kundali_request_info_id;// for custom amount 
            $obj->order_id = $data->order_id;
            $obj->billing_name = $data->name;
            $obj->billing_mobile = $data->mobile;
            $obj->billing_email = $data->email;
            $obj->billing_address = $data->address;
            $obj->billing_state = $data->state_id;
            $obj->billing_country = $data->country_id;
            $obj->zip_code = $data->postcode;
            $obj->product_type = $data->product_type;
            $obj->amount = $data->amount;
            $obj->shipping_charge = $data->shipping_charge;
            $obj->tax_percentage = $data->tax_percentage;
            $obj->tax_amount = $data->tax_amount;
            $obj->discount = $data->discount;
            $obj->coupon_data = $data->coupon_data;
            $obj->order_status = $data->order_status;
            $obj->device_type=$data->device_type;
            if($obj->save()){
                return true;
            }
            return false;

    }
}
