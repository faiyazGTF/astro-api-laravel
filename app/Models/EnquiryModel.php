<?php

namespace App\Models;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnquiryModel extends Model
{
    use HasFactory;
    protected $table = 'enquiry';

    public static function PostEnquiry($name,$email,$mobile,$subject,$message,$query_type,$status=0){

        
        $obj = new self();

        $getuser = User::where('mobile', $mobile)
            ->orderBy('id', 'desc')
            ->first();

        if(!empty($getuser->id)){
            $obj->user_id=$getuser->id;
        }

        $obj->full_name = $name;
        $obj->email = $email;
        $obj->phone = $mobile;
        $obj->subject = $subject;
        $obj->description = $message;
        $obj->query_type = $query_type;
        $obj->status = $status;
        if($obj->save()){
            return response()->json([
                'statusCode'=>200,
                'status'=>true,
                'message'=>'success',
            ]);
        }
        return response()->json([
            'statusCode'=>500,
            'status'=>false,
            'message'=>'something went wrong',
        ]);
    }
}
