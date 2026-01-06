<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletsModel extends Model
{
    use HasFactory;
    protected $table = "wallets";

    public static function AddWalletRecord($user_id,$transaction_id,$transactionType,$type,$amount,$userupdatedbalance,$transaction_by){
        try {
            $userWalletObj = new self();
            $userWalletObj->user_id          = $user_id;
            $userWalletObj->transaction_id   = $transaction_id;
            $userWalletObj->transaction_type = $transactionType;
            $userWalletObj->product_type     = $type;
            $userWalletObj->amount           = $amount;
            $userWalletObj->balance_amount   = $userupdatedbalance;
            $userWalletObj->transaction_by   = $transaction_by;
          
            $userWalletObj->save();
   
            $updatebalanceamount=UsersDetail::where('user_id',$user_id)->first();
            $updatebalanceamount->balance_amount=$userupdatedbalance;
            $updatebalanceamount->save();
            return true;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
    
}
