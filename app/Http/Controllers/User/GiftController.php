<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Gifts;
use App\Models\User\User;
use App\Models\User\WalletsModel;
use App\Http\Controllers\FireBaseActionController; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GiftController extends Controller
{
    //
    public function index(Request $request){
       $result=Gifts::list($request);
       return $result;
    }
    public function giftDetails($giftId){
        try {
        $result=Gifts::Details($giftId);
        return ApiResponse(200,true,'success',$result);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function  shareGift(Request $request,$giftid)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'expirt_id' => 'required|exists:users,id',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
		
            $userdata = User::select('users.id','users.name','users.image','users_details.balance_amount')->join('users_details', 'users_details.user_id', '=', 'users.id')
            ->where('users.id', $request->user_id)
            ->where('users.user_type', 'USER')
            ->first();
              $astrodata= User::select('users.id','users.name','users_details.balance_amount','users_details.gift_commission')->join('users_details', 'users_details.user_id', '=', 'users.id')
            ->where('users.id', $request->expirt_id)
            ->where('users.user_type', 'ASTROLOGER')->first();
      

            $giftdata=Gifts::find($giftid);
              
            if(!$userdata || !$astrodata  || !$giftdata){
                return SimpleResponse(403,false,'Invalid User ,astro id  or  invalid gift');
            }
            if ($userdata->balance_amount<$giftdata->amount) {
                return SimpleResponse(403,false,"You don't have enough balance to buy a gift!!");
            }


              /* Expert Wallet Data Save */
              $type='Gift Amount';
             // $transaction_id    = 'GFT_' . time() . rand(100, 999);
			 $transaction_id    = $giftdata->id;
			
              $userupdatedbalance=$userdata->balance_amount-$giftdata->amount;
          
              $expertupdatedbalance=$astrodata->balance_amount+$giftdata->amount;
			
			  $giftCommission    = $astrodata->gift_commission;
			
			  $expeertgiftamountaftercommison=round(($giftdata->amount * $giftCommission) / 100, 2);

              $expertupdatedbalance=$astrodata->balance_amount+$expeertgiftamountaftercommison;		

          
        $result111=WalletsModel::AddWalletRecord($userdata->id,$transaction_id,'debits',$type,$giftdata->amount,$userupdatedbalance,'user');
     
          WalletsModel::AddWalletRecord($astrodata->id,$transaction_id,'credits',$type,$expeertgiftamountaftercommison,$expertupdatedbalance,'user');

            $getfcmtoken = getFcmToken($request->expirt_id);
			
            $notificationarray = [
                'title' => 'New Gift from ' . $userdata->name,
                'message' => $userdata->name . ' just sent you a gift! Check it out now.',
                'image' => image_url($userdata->image,'/public/cms-images/user-images/'),
                'type' => 'Gift',
                'senderid' => $userdata->id
            ];
			
            FireBaseActionController::PushNOtificationAuthdata($getfcmtoken, $notificationarray);
			
            return ApiResponse(200,true,'Thanks for the gift',$giftdata);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }

    }
    
}
