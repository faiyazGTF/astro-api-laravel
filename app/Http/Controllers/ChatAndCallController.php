<?php

namespace App\Http\Controllers;

use App\Http\Controllers\User\FreeKundaliController;
use App\Models\CallChatRequest;
use App\Models\ChatMessages;
use App\Models\ConsultRemedies;
use App\Models\Payment;
use App\Models\PoojaModel;
use App\Models\User\User;
use App\Models\User\UsersDetail;
use App\Models\User\WalletsModel;
use App\Models\StatusList;
use App\Models\User\ReviewsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use PDO;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\SwitchSession;
use PhpParser\Node\Stmt\TryCatch;

class ChatAndCallController extends Controller
{
    //
    private function getAstroDetails($astroid)
    {

        $expertdata = User::select('users_details.flags', 'users_details.astro_video_charges', 'users_details.video_commission', 'users_details.disc_video_charge', 'users_details.call_commission', 'users.image', 'users_details.chat_commission', 'users_details.disc_call_charge', 'users_details.astro_call_charges', 'users_details.is_promotional_accept', 'users_details.astro_chat_charges', 'users_details.disc_chat_charge', 'users_details.availability', 'users.id', 'users.name')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $astroid)->first();

        if ($expertdata) {
            

           $expertdata->image= image_url($expertdata->image,'/public/cms-images/user-images/');
            return $expertdata;
        }
        return false;
    }




    public function CheckAvability(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'expert_id' => 'required|exists:users,id',
                'user_id' => 'required|exists:users,id',
                'request_type' => 'required|in:Call,Chat,video',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $userdata = User::select('users.id', 'users.name', 'users_details.balance_amount')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $request->user_id)->first();

            if ($userdata) {

                $astrodata = $this->getAstroDetails(astroid: $request->expert_id);

                // add new for check first user
                $promotional = $astrodata->is_promotional_accept;

                if (!$astrodata || $astrodata->availability == 0) {

                    $min_recharage = DB::select("SELECT talk_time_amount, package_name FROM `recharge_packages` WHERE `only_once` = 1");

                    return response()->json([
                        'statusCode' => 202,
                        'status' => false,
                        // 'message'=> 'You need a minimum balance. ₹ ' . $min_recharage[0]->talk_time_amount . ' is required to start a chat with ' . $astrodata->name . '.',
                        'message' => 'Astrologer ' . $astrodata->name . ' is currently offline.',
                    ]);
                } else if ($userdata->balance_amount == 0) {

                    $user_mobile = DB::table('users')->where('id', $request->user_id)->value('mobile');

                    $existingDeletedUsers = DB::table('users')
                        ->where('mobile', $user_mobile)
                        ->where('is_deleted', 1)
                        ->exists();

                    // check if user has previously done chat
                    $user_has_chatted = DB::table('call_chat_request')
                        ->where('user_id', $request->user_id)
                        ->where('request_type', 'Chat')
                        ->whereIn('request_status', [2, 5])
                        ->exists();

                    $user_has_recharged_once = DB::table('payments')
                        ->where('user_id', $request->user_id)
                        ->where('payment_status', 'Completed')
                        ->exists();

                    if ($request->request_type == 'Chat') {

                        if ($user_has_chatted || $existingDeletedUsers) {
                            // Already chatted → show regular recharge
                            $asro_charges = !empty($astrodata->disc_chat_charge)
                                ? $astrodata->disc_chat_charge
                                : $astrodata->astro_chat_charges;
                        } elseif (!$user_has_chatted && $user_has_recharged_once) {
                            // First time chatting but already recharged

                            $asro_charges = "";

                            $responseobject = [
                                'is_firstuser_active' => 0,
                                'request_type' => $request->request_type,
                                'is_promotional' => $promotional,
                                'expert' => [
                                    'id' => $astrodata->id,
                                    'name' => $astrodata->name,
                                    'astro_charges' => 0
                                ],
                                'user' => $userdata
                            ];

                            return ApiResponse(200, true, 'success', $responseobject);
                        } else {

                            // First time chat → show one-time package
                            $min_recharge = DB::select("SELECT package_amount, talk_time_amount, package_name FROM recharge_packages WHERE only_once = 1 LIMIT 1");
                            if (!empty($min_recharge)) {
                                return response()->json([
                                    'statusCode' => 201,
                                    'status' => false,
                                    'message' => 'You need to recharge with Rs' . $min_recharge[0]->package_amount . ' in your wallet to avail free ' . $request->request_type
                                ]);
                            }
                        }
                    } elseif ($request->request_type == 'video') {
                        $asro_charges = !empty($astrodata->disc_video_charge)
                            ? $astrodata->disc_video_charge
                            : $astrodata->astro_video_charges;
                    } else {
                        // Assume it's a call
                        $asro_charges = !empty($astrodata->disc_call_charge)
                            ? $astrodata->disc_call_charge
                            : $astrodata->astro_call_charges;
                    }

                    return response()->json([
                        'statusCode' => 201,
                        'status' => false,
                        'message' => 'You need minimum balance of 5 minutes, ₹' . ($asro_charges * 5) . ' is required in your wallet to start ' . $request->request_type . ' with ' . $astrodata->name
                    ]);
                }

                $CheckUserChatStatus = $this->CheckAlreadyInChat($request->user_id);

                if ($CheckUserChatStatus == 'success') {

                    $flags = explode(',', $astrodata->flags);

                    if (!in_array(strtolower($request->request_type), $flags)) {
                        return ApiResponse(203, false, $astrodata->name . ' does not accept ' . $request->request_type);
                    }

                    // check if user is eligible for free first-time chat
                    if ($request->request_type == 'Chat') {
                        $user_has_chatted = DB::table('call_chat_request')
                            ->where('user_id', $request->user_id)
                            ->where('request_type', 'Chat')
                            ->whereIn('request_status', [2, 5])
                            ->exists();

                        $user_mobile = DB::table('users')->where('id', $request->user_id)->value('mobile');

                        $existingDeletedUsers = DB::table('users')
                            ->where('mobile', $user_mobile)
                            ->where('is_deleted', 1)
                            ->exists();
                        if (!$user_has_chatted && $promotional == 1 && !$existingDeletedUsers) {
                            // Allow free chat without balance check

                            $asro_charges = "";

                            $responseobject = [
                                'is_firstuser_active' => 0,
                                'request_type' => $request->request_type,
                                'is_promotional' => $promotional,
                                'expert' => [
                                    'id' => $astrodata->id,
                                    'name' => $astrodata->name,
                                    'astro_charges' => 0
                                ],
                                'user' => $userdata
                            ];

                            return ApiResponse(200, true, 'success', $responseobject);
                        }
                    }

                    $asro_charges = "";
                    $is_firstuser_active = 0;


                    if ($request->request_type == 'Chat') {
                        $asro_charges = !empty($astrodata->disc_chat_charge) ? $astrodata->disc_chat_charge : $astrodata->astro_chat_charges;
                    } else if ($request->request_type == 'video') {
                        $asro_charges = !empty($astrodata->disc_video_charge) ? $astrodata->disc_video_charge : $astrodata->astro_video_charges;
                        $promotional = 0;
                    } else {
                        $asro_charges = !empty($astrodata->disc_call_charge) ? $astrodata->disc_call_charge : $astrodata->astro_call_charges;
                        $promotional = 0;
                    }
                    //	$promotional = 0;

                    $astroFiveMinCharge1 = ($asro_charges * 5);

                    if (in_array($request->request_type, ['Chat', 'Call', 'video']) && $userdata->balance_amount < $astroFiveMinCharge1) {

                        return response()->json([
                            'statusCode' => 201,
                            'status' => false,
                            'message' => 'You need minimum balance of 5 minutes, ₹' . $astroFiveMinCharge1 . ' is required in your wallet to start ' . $request->request_type . ' with ' . $astrodata->name
                        ]);
                    }

                    $responseobject['is_promotional'] = 0;
                    $responseobject = [
                        'is_firstuser_active' => $is_firstuser_active,
                        'request_type' => $request->request_type,
                        'is_promotional' => $promotional,
                        'expert' => [
                            'id' => $astrodata->id,
                            'name' => $astrodata->name,
                            'astro_charges' => $asro_charges
                        ],
                        'user' => $userdata
                    ];

                    //  and request_type='Chat'
                    $user_last_call = DB::select(
                        "SELECT id  FROM `call_chat_request` WHERE `user_id` = " . $userdata->id . " and (`request_status` = '5' OR request_status='2')"
                    );

                    if ($user_last_call) {

                        $responseobject['is_promotional'] = 0;

                        $astroFiveMinCharge = ($asro_charges * 5);
                        if ($astroFiveMinCharge > $userdata->balance_amount) {
                            return SimpleResponse(
                                201,
                                false,
                                'You need minimum balance of 5 minutes, ₹' . $astroFiveMinCharge . ' is required in your wallet to start ' . $request->request_type . ' with ' . $astrodata->name . '.'
                            );
                        }
                    }
                    return ApiResponse(200, true, 'success', $responseobject);
                }
                return SimpleResponse(204, false, $CheckUserChatStatus);
            }
            return SimpleResponse(404, false, 'Invalid user or astrologer');
        } catch (\Throwable $th) {
            return SimpleResponse(500, false, $th->getMessage());
        }
    }


    public function  checkuserOnaction($expert_id, $user_id, $request_type)
    {
        try {
            $userdata = User::select('users.id', 'users.name', 'users_details.balance_amount')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $user_id)->first();

            if ($userdata) {
                $astrodata = $this->getAstroDetails($expert_id);
                if (!$astrodata || $astrodata->availability == 0) {
                    return $astrodata->name . ' is offline';
                }
                $CheckUserChatStatus = $this->CheckAlreadyInChat($user_id);
                if ($CheckUserChatStatus == 'success') {
                    if ($request_type == 'Chat') {
                        $asro_charges = !empty($astrodata->disc_chat_charge) ? $astrodata->disc_chat_charge : $astrodata->astro_chat_charges;
                    } else if ($request_type == 'Video') {
                        $asro_charges = !empty($astrodata->disc_chat_charge) ? $astrodata->disc_chat_charge : $astrodata->astro_chat_charges;
                        $promotional = 0;
                    } else {
                        $asro_charges = !empty($astrodata->disc_call_charge) ? $astrodata->disc_call_charge : $astrodata->astro_call_charges;
                    }

                    $user_last_call = DB::select(
                        "SELECT id  FROM `call_chat_request` WHERE `user_id` = " . $userdata->id . " and (`request_status` = '5' OR request_status='2') and request_type='Chat'"
                    );

                    if ($user_last_call) {
                        $astroFiveMinCharge = ($asro_charges * 5);
                        if ($astroFiveMinCharge > $userdata->balance_amount) {
                            return 'You need minimum balance of 5 minutes, ₹' . $astroFiveMinCharge . ' is required in your wallet to start ' . $request_type . ' with ' . $astrodata->name . ' ';
                        }
                    }
                }
                return true;
            }
        } catch (\Throwable $th) {
            return SimpleResponse(500, false, $th->getMessage());
        }
    }


    protected function CheckAlreadyInChat($user_id)
    {

        try {
            $checkexists = CallChatRequest::where('user_id', $user_id)
                ->whereIn('request_status', [2, 20, 1])
                ->whereIn('request_type', ['Chat', 'Calling', 'Video'])->first();

            if ($checkexists) {
                return 'You have already initiated a ' . $checkexists->request_type . ' session';
            } else {
                return 'success';
            }
        } catch (\Throwable $th) {
            return SimpleResponse(500, false, $th->getMessage());
        }
    }
    protected function CheckAlreadyInCall($user_id)
    {
        $checkexists = CallChatRequest::where('user_id', $user_id)
            ->whereIn('request_status', [2, 20, 1])
            ->whereIn('request_type', ['Chat', 'Calling', 'Video'])->exists();
        if ($checkexists) {
            return 'You have already initiated a call';
        } else {
            return 'success';
        }
    }
    protected function CheckAstroBusy($astroId)
    {
        $expertdata = UsersDetail::where('user_id', $astroId)->where('availability', 2)->doesntExist();
        $callchatrequest = CallChatRequest::where('expert_id', $astroId)->whereIn('request_status', [1, 2])->doesntExist();

        if (!$expertdata || !$callchatrequest) {
            return false;
        }
        return true;
    }



    public function userWalletDebitForCalling($user_id, $totDuration, $astroCharges, $session_id, $expert_id, $callRequestType, $is_promotional, $chatstatus = 0)
    {
        $walObjs = WalletsModel::where('transaction_id', $session_id)->first();

        $userdata = UsersDetail::where('user_id', $user_id)->first();
        $callRequestType = Str::lower($callRequestType);
        if (empty($walObjs)) {

            $totDurations = ($is_promotional == 1 && $callRequestType == 'chat') ? max(0, $totDuration - 300) : $totDuration;
            $totAmountDebits = ceil($totDurations / 60) * $astroCharges;


            if ($is_promotional == 1 && $callRequestType == 'chat') {
                $totAmountDebits = 0;
                $bal = max(0, $userdata->balance_amount);
            } else {
                $bal = max(0, $userdata->balance_amount - $totAmountDebits);
                //  $totAmountDebits = ($totAmountDebits > $userdata->balance_amount) ? 0 : $totAmountDebits;
            }

            WalletsModel::AddWalletRecord($user_id, $session_id, 'debits', $callRequestType, $totAmountDebits, $bal, 'user');
            UpdateUserLedger('user', $user_id, $session_id, $userdata->balance_amount, $bal, $totAmountDebits, 1, $is_promotional, $chatstatus, 'debits');
            // update user balance duduct amount save 
            $userdata->balance_amount = $bal;
            $userdata->save();

            //Astrologer credit entry
            $commissions = [
                'chat' => 'chat_commission',
                'video' => 'video_commission',
            ];
            $astroDetails = UsersDetail::where('user_id', $expert_id)->first();
            $call_astro_commission = $astroDetails->{$commissions[$callRequestType] ?? 'call_commission'};
            $astroPayAmount = $call_astro_commission > 0 ? (int) ($astroCharges * $call_astro_commission) / 100 : 0;
            $astroCallCast = $astroPayAmount * ceil($totDurations / 60);

            if ($is_promotional == 1 && $callRequestType == 'chat') {
                // $time1_chat=0; 
                // $time2_chat=0;  
                // $checkmessage = ChatMessages::where('request_session_id', $session_id)->exists();
                // if($checkmessage){  
                //     $user_chat_duration = DB::select("SELECT (SELECT created_at FROM chat_messages WHERE request_session_id='$session_id' ORDER BY id LIMIT 1) as 'first', (SELECT created_at FROM chat_messages WHERE request_session_id='$session_id' ORDER BY id DESC LIMIT 1) as 'last'");
                //     if(!empty($user_chat_duration[0]->first)) {
                //         $time1_chat = strtotime($user_chat_duration[0]->first);
                //         $time2_chat = strtotime($user_chat_duration[0]->last);
                //     } 
                // }
                // $totDuration_by_chat= $time2_chat - $time1_chat;
                // if ($totDuration_by_chat >= 60 && $totDuration_by_chat <= 120) {
                //     $topop_balance=0;
                // } elseif ($totDuration_by_chat >=121 && $totDuration_by_chat <= 180) {                      
                //     $topop_balance=3;
                // } elseif ($totDuration_by_chat >=181 && $totDuration_by_chat <= 240) {
                //     $topop_balance=3;
                // }else {
                //     $topop_balance=0;
                // }

                $user_chat_duration_count2 = DB::select("
                        SELECT COUNT(cm.id) AS counts 
                        FROM chat_messages AS cm
                        LEFT JOIN users AS u ON u.id = cm.user_id
                        WHERE cm.request_session_id = ? AND cm.user_id = ?
                        LIMIT 1
                    ", [$session_id, $expert_id]);

                $chatCount = !empty($user_chat_duration_count2) ? $user_chat_duration_count2[0]->counts : 0;

                $topop_balance = ($chatCount >= 4) ? 1 : 0; // 3

                $astroBal = $astroDetails->balance_amount + $topop_balance;

                WalletsModel::AddWalletRecord($expert_id, $session_id, 'credits', $callRequestType, $topop_balance, $astroBal, 'user');
            } else {
                $user_chat_duration_count2 = DB::select("
                        SELECT COUNT(cm.id) AS counts 
                        FROM chat_messages AS cm
                        LEFT JOIN users AS u ON u.id = cm.user_id
                        WHERE cm.request_session_id = ? AND cm.user_id = ?
                        LIMIT 1
                    ", [$session_id, $expert_id]);

                $chatCount = !empty($user_chat_duration_count2) ? $user_chat_duration_count2[0]->counts : 0;

                if ($callRequestType == 'chat') {
                    if ($chatCount < 4) {
                        $astroCallCast = 0;
                    }
                    $astroBal = $astroDetails->balance_amount + $astroCallCast;
                    WalletsModel::AddWalletRecord($expert_id, $session_id, 'credits', $callRequestType, $astroCallCast, $astroBal, 'user');
                } else {
                    $astroBal = $astroDetails->balance_amount + $astroCallCast;

                    $expertWalObj = new WalletsModel();
                    $expertWalObj->user_id = $expert_id;
                    $expertWalObj->transaction_id = $session_id;
                    $expertWalObj->transaction_type = 'credits';
                    $expertWalObj->product_type = Str::lower($callRequestType);
                    $expertWalObj->transaction_by = 'user';
                    $expertWalObj->balance_amount = $astroBal;
                    $expertWalObj->amount = $astroCallCast;

                    WalletsModel::AddWalletRecord($expert_id, $session_id, 'credits', $callRequestType, $astroCallCast, $astroBal, 'user');
                }
            }
            $astroDetails->balance_amount = $astroBal;
            $astroDetails->availability = 1;
            $astroDetails->save();
        }
    }
    public function endSwitchRequest(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'chat_id' => 'required',
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        try {
            $checkrecord = CallChatRequest::where('request_session_id', $request->chat_id)->first();
            $checswitch = SwitchSession::where('session_id', $request->chat_id)->first();

            if ($checkrecord && $checswitch) {
                $currentTime = date('Y-m-d H:i:s', time());
                $callRequestType = $checkrecord->request_type;
                $session_id = $request->chat_id;
                $expert_id = $checkrecord->expert_id;
                FireBaseActionController::SwitchSessionEvents($expert_id, $checkrecord->user_id, '', '', $session_id, 'Canceled', $checswitch->switch_to, '', '', '', '');
                $checswitch->status = 2; // canceled
                $checkrecord->save();
                return SimpleResponse(200, true, 'Session Canceled');
            }
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function endChat(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'chat_id' => 'required',
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        try {
          
            $request_chatid = $request->chat_id;
            $checkrecord = CallChatRequest::where('request_session_id', $request->chat_id)->first();
            
            if (!$checkrecord) {
                return SimpleResponse(403, false, 'Invalid Chat id or Unauthprized User id');
            } elseif ($checkrecord->request_status == 7 || $checkrecord->request_status == 8 || $checkrecord->request_status == 5) {
                return SimpleResponse(403, false, 'You do not have access');
            }
            $usersend = $request->user_id;
            $callRequestType = $checkrecord->request_type;
            $session_id = $request->chat_id;
            $expert_id = $checkrecord->expert_id;
            $currentstatus = $checkrecord->request_status;
         
            if ($checkrecord->request_status != 20) {



                $show_time = DB::select("SELECT  round(ud.balance_amount/ccr.astro_call_chagre)*60 as call_time FROM call_chat_request as ccr
                        LEFT JOIN users_details as ud on ud.user_id=ccr.user_id
                        WHERE ccr.request_session_id='$request_chatid'");

                $currentTime = Carbon::now()->format('Y-m-d H:i:s'); // e.g., 2025-07-15 19:12:00


                if (!empty($request->end_time)) {
                    //	echo 'jiiiiiii';
                    $currentTime = $request->end_time;
                }

                //end define variable
                $checkrecord->user_end_time = $currentTime;
                $checkrecord->astro_end_time = $currentTime;

                $totDuration = getTotalDuration($checkrecord);
                $starttimgpro = strtotime($checkrecord->astro_start_time);
                $endtimgpro = strtotime($currentTime);
                $totalpromotoanlduration = $endtimgpro - $starttimgpro;

                if ($checkrecord->is_promotional == 1 &&  $totalpromotoanlduration > 170) {
                    $totDuration = 180;
                }

        
                if ($checkrecord->total_duration == 0) {
                    $time1 = strtotime($checkrecord->astro_start_time);
                    $time2 = time();
                    $diff = $time2 - $time1 - 5;

                    $checkrecord->total_duration = $totDuration;
                  
                    if ($checkrecord->request_status == 2) {
                        if ($callRequestType == 'Video' || $callRequestType == 'video') {
                            $this->userWalletDebitForCalling($checkrecord->user_id, $totDuration, $checkrecord->astro_video_call_charge, $session_id, $expert_id, $callRequestType, $checkrecord->is_promotional, $checkrecord->request_status);
                        } elseif ($callRequestType == 'Chat') {
                            $this->userWalletDebitForCalling($checkrecord->user_id, $totDuration, $checkrecord->astro_chat_charge, $session_id, $expert_id, $callRequestType, $checkrecord->is_promotional, $checkrecord->request_status);
                        } else {
                            $this->userWalletDebitForCalling($checkrecord->user_id, $totDuration, $checkrecord->astro_call_chagre, $session_id, $expert_id, $callRequestType, $checkrecord->is_promotional, $checkrecord->request_status);
                        }
                    }
                }
            }
           

            if (in_array($checkrecord->request_status, [1, 2, 20])) {
                if ($checkrecord->request_status == 1 || $checkrecord->request_status == 20) {
                    $checkrecord->request_status = 7;
                } else {
                    $checkrecord->request_status = 5;
                }

                $checkrecord->request_status_log = $checkrecord->request_status == 7 ? 'Cancelled by User' : 'Ended by User';
            }
            $getstatusname = StatusList::where('order_status_id', $checkrecord->request_status)->first();
            $statusnameresut = 'Unknown';
            if ($getstatusname) {
                $statusnameresut = $getstatusname->name;
            }
            $newarray = [
                'status' => $statusnameresut,
                'end_by' => $usersend,
            ];
            $astrodata = UsersDetail::where('user_id', $expert_id)->first();
      
         
            if ($currentstatus != 20) {
               $resultfirebase= FireBaseActionController::AstrologerConsultUpdateNew($checkrecord->expert_id, $checkrecord->user_id, $checkrecord->user_name, '', 0, $callRequestType, $session_id, $newarray);
             
                // UsersDetail::where('user_id', $expert_id)->update(['availability' => 1]);
                $astrodata->availability = 1;
                $astrodata->save();

                FireBaseActionController::SwitchSessionEvents($expert_id, '', '', '', '', '', '', '', '');
            } elseif ($astrodata->availability == 1) {

                FireBaseActionController::AstrologerConsultUpdateNew($checkrecord->expert_id, $checkrecord->user_id, $checkrecord->user_name, '', 0, $callRequestType, $session_id, $newarray);
            } elseif ($astrodata->availability == 2 && $currentstatus == 20) {

                $getusergety = User::find($usersend);

                if ($getusergety->user_type === 'USER' && $checkrecord->request_status != 5) {

                    $getuser = User::find($checkrecord->user_id);
                    $getfcmtoken = getFcmToken($checkrecord->user_id);

                    $notificationarray = [
                        'title'     => 'New Message from ' . $getuser->name,
                        'message'   => 'User cancelled your request chat',
                        'image'     =>image_url($getuser->image,'/public/cms-images/user-images/'),
                        'type'      => 'chat',
                        'senderid'  => $getuser->id
                    ];

                    FireBaseActionController::PushNOtificationAuthdata($getfcmtoken, $notificationarray);

                    $checkrecord->request_status = 8;
                    $checkrecord->request_status_log = 'Cancelled by User';
                } else {
                    $getuser = User::find($checkrecord->user_id);
                    $getfcmtoken = getFcmToken($checkrecord->user_id);

                    $notificationarray = [
                        'title' => 'New Message from ' . $getuser->name,
                        'message' => 'Astrologer cancel your request chat',
                        'image' => image_url($getuser->image,'/public/cms-images/user-images/'),
                        'type' => 'chat',
                        'senderid' => $getuser->id
                    ];

                    FireBaseActionController::PushNOtificationAuthdata($getfcmtoken, $notificationarray);
                    $checkrecord->request_status = 8;
                    $checkrecord->request_status_log = 'Cancelled by Astrologer';
                }
            }
           

          $resultresponse=  $checkrecord->save();

            // End Socket Timer
            try {
                Http::post(env('SOCKET_SERVER_URL', 'http://localhost:65282') . '/kill-server-timer', [
                    'room' => $request_chatid
                ]);
            } catch (\Throwable $e) {
                \Log::error("Socket Timer Kill Failed: " . $e->getMessage());
            }

            return ApiResponse(200, true, 'Chat End', $checkrecord);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function ConsultHistory(Request $request, $user_id)
    {
        $result = CallChatRequest::getConsultHistory($request, $user_id);
        return ApiResponse(200, true, 'success', $result);
    }

    public function ConsultHistoryhome(Request $request, $user_id)
    {
        $result = CallChatRequest::getConsultHistoryhome($request, $user_id);
        return ApiResponse(200, true, 'success', $result);
    }

    public function getConsultHistoryAstrologer(Request $request, $user_id)
    {
        $result = CallChatRequest::getConsultHistoryAstrologer($request, $user_id);
        return ApiResponse(200, true, 'success', $result);
    }
    public function ChatHistory(Request $request, $cousultId)
    {
        $result = CallChatRequest::ChatHistory($request, $cousultId);
        return ApiResponse(200, true, 'success', $result);
    }

    public function StartChat(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'fullname' => 'required',
            'customer_mobile' => 'required|numeric',
            'birthDate' => 'required|date',
            'birthtime' => 'required',
            'birthPlace' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'expert_id' => 'required|exists:users,id',
            'user_id' => 'required|exists:users,id',
            'is_promonional' => 'required|in:0,1',
            'timezone' => 'required',
            'gender' => 'required'
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
            die;
        }
        try {

            $requestype = 'Chat';
            $checkagainavailability = $this->checkuserOnaction($request->expert_id, $request->user_id, $requestype);

            if ($checkagainavailability != true) {

                return ApiResponse(403, true, 'false', $checkagainavailability);
            }
            $checkalreadychat = $this->CheckAlreadyInChat($request->user_id);
            if ($checkalreadychat != 'success') {
                return ApiResponse(403, true, 'false', $checkalreadychat);
            }
            CallChatRequest::CancelActiveAllCallChat($request->user_id); // disabale all active chat for this user
            $checkUser = User::select('users.user_type', 'users_details.balance_amount', 'users.id', 'users.image')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $request->user_id)->first();
            $astrodata = $this->getAstroDetails($request->expert_id);


            if ($checkUser->user_type == "USER" && !empty($astrodata)) {
                $CheckAstroBusy = $this->CheckAstroBusy($astrodata->id);
                $request_status = !$CheckAstroBusy ? 20 : 1;
                $asro_charges = !empty($astrodata->disc_chat_charge) ? $astrodata->disc_chat_charge : $astrodata->astro_chat_charges;
                $chat_commission = $astrodata->chat_commission;



                if ($request->is_promonional == 1 && $requestype = 'Chat') {

                    //  $asro_charges=1;
                    $chat_commission = 100;
                }
                $chatid = generateConsultId('CH');

                $device_type = 1;
                if (!empty($request->device_type)) {
                    $device_type = $request->device_type;
                }

                $requestData = [
                    'user_id' => $checkUser->id,
                    'expert_id' => $astrodata->id,
                    'user_name' => $request->fullname,
                    'request_type' => 'Chat',
                    'chat_commission' => $chat_commission,
                    'astro_chat_charge' => $asro_charges,
                    'waitlist_status' => $request_status == 1 ? 0 : 20, // adding wating count 
                    'form_meta' => serialize($_POST),
                    'device_type' => $device_type, // 1 => website ,2 =>app,
                    'is_promotional' => $request->is_promonional,
                    'request_session_id' => $chatid,
                    'start_session_date' => date("Y-m-d H:i:s", time()),
                    'request_expired' => date("Y-m-d H:i:s", strtotime("+1 day", time())),
                    'request_status' => $request_status,
                    'user_start_time' => date("Y-m-d H:i:s", time()),
                    'new_api'=>1

                ];
                $saverecord = CallChatRequest::create($requestData); /// saving chat request
                if ($saverecord) {
                    $saverecord->expert_details = $astrodata;
                    $user_image = !empty($checkUser->image)
                        ? image_url($checkUser->image,'/public/cms-images/user-images/')
                        : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
                    // $status =$request_status== 2 ?  "active" :"complete" ;
                    $saverecord->form_meta = unserialize($saverecord->form_meta);
                    $status = "initiate";


                    $requestdata = [
                        'full_name' => $request->fullname,
                        'birth_date' => $request->birthDate,
                        'birth_time' => Carbon::parse($request->birthtime),
                        'gender' => $request->gender,
                        'place' => $request->birthPlace,
                        'lat' => $request->latitude,
                        'long' => $request->longitude,
                        'time_zone' => $request->timezone,
                        'lang' => 'en',
                    ];


                    $saverecord->report = FreeKundaliController::GeneralReport($requestdata);
                    $saverecord->max_end_time = getmaxExpectedTime($saverecord->request_session_id);


                    if ($request_status == 1) {
                        FireBaseActionController::new_notification_firbase_hits_ivent('Chat', $chatid, $astrodata->id, $request->fullname);
                        FireBaseActionController::AstrologerConsultUpdate($astrodata->id, $checkUser->id, $request->fullname, $user_image, $checkUser->balance_amount, 'Chat', $chatid, $status);
                    }

                    $getfcmtoken = getFcmToken($astrodata->id);
                    if ($getfcmtoken) {
                        // FireBaseActionController::PushNOtification($getfcmtoken,'New Session Request','New Chat request from  '.$request->fullname.'',$user_image,$type='Chat' ?? '');
                        $type = 'chat'; // ensure it's defined beforehand
                        FireBaseActionController::PushNOtification($getfcmtoken, 'New Session Request', 'New Chat request from  ' . $request->fullname . '', $user_image, $type);
                    }



                    return ApiResponse(200, true, 'success', $saverecord);
                }
            }
            return SimpleResponse(403, false, 'Invalid User Type');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function StartCalling(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required',
            'customer_mobile' => 'required|numeric',
            'birthDate' => 'required|date',
            'birthtime' => 'required',
            'birthPlace' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'expert_id' => 'required|exists:users,id',
            'user_id' => 'required|exists:users,id',
            'is_promonional' => 'required|in:0,1',
            'timezone' => 'required',
            'gender' => 'required'
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        try {
            $requestype = 'Calling';

            $checkagainavailability = $this->checkuserOnaction($request->expert_id, $request->user_id, $requestype);
            if ($checkagainavailability != true) {

                return ApiResponse(403, true, 'false', $checkagainavailability);
            }
            $checkalreadychat = $this->CheckAlreadyInCall($request->user_id);
            if ($checkalreadychat != 'success') {
                return ApiResponse(403, false, $checkalreadychat);
            }
            CallChatRequest::CancelActiveAllCallChat($request->user_id); // disabale all active chat for this user
            $checkUser = User::select('users.user_type', 'users_details.balance_amount', 'users.id', 'users.image','users_details.profile_image as image')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $request->user_id)->first();
            $astrodata = $this->getAstroDetails($request->expert_id);
            if ($checkUser->user_type == "USER" && !empty($astrodata)) {
                $CheckAstroBusy = $this->CheckAstroBusy($astrodata->id);



                $request_status = !$CheckAstroBusy ? 20 : 1;

                $asro_charges = !empty($astrodata->disc_call_charge) ? $astrodata->disc_call_charge : $astrodata->astro_call_charges;

                $call_commission = $astrodata->call_commission;
                if ($request->is_promonional == 1) {
                    $asro_charges = 1;
                    $call_commission = 100;
                }
                $chatid = generateConsultId('CAL');
                $device_type = 1;
                if (!empty($request->device_type)) {
                    $device_type = $request->device_type;
                }
                $requestData = [
                    'user_id' => $checkUser->id,
                    'expert_id' => $astrodata->id,
                    'user_name' => $request->fullname,
                    'request_type' => $requestype,
                    'call_commission' => $call_commission,
                    'astro_call_chagre' => $asro_charges,
                    'waitlist_status' => $request_status == 1 ? 0 : 20, // adding wating count 
                    'form_meta' => serialize($_POST),
                    'device_type' => $device_type, // 1 => android ,2 =>ios,
                    'is_promotional' => $request->is_promonional,
                    'request_session_id' => $chatid,
                    'start_session_date' => date("Y-m-d H:i:s", time()),
                    'request_expired' => date("Y-m-d H:i:s", strtotime("+1 day", time())),
                    'request_status' => $request_status,
                    'user_start_time' => date("Y-m-d H:i:s", time()),
                    'new_api'=>1
                ];
                $saverecord = CallChatRequest::create($requestData); /// saving chat request
                if ($saverecord) {
                    $saverecord->expert_details = $astrodata;
                    $user_image = !empty($checkUser->image)
                        ? image_url($checkUser->image,'/public/cms-images/user-images/')
                        : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
                    // $status =$request_status== 2 ?  "active" :"complete" ;
                    $status = "initiate";
                    $saverecord->form_meta = unserialize($saverecord->form_meta);
                    if ($request_status == 1) {
                        FireBaseActionController::new_notification_firbase_hits_ivent($requestype, $chatid, $astrodata->id, $request->fullname, $request->customer_mobile);
                        FireBaseActionController::AstrologerConsultUpdate($astrodata->id, $checkUser->id, $request->fullname, $user_image, $checkUser->balance_amount, $requestype, $chatid, $status);
                    }

                    $getfcmtoken = getFcmToken($astrodata->id);
                    if ($getfcmtoken) {
                        // FireBaseActionController::PushNOtification($getfcmtoken,'New Session Request','New Chat request from  '.$request->fullname.'',$user_image,$type='Chat' ?? '');
                        $type = 'call'; // ensure it's defined beforehand
                        FireBaseActionController::PushNOtification($getfcmtoken, 'New Session Request', 'New call request from  ' . $request->fullname . '', $user_image, $type);
                    }

                    $saverecord->max_end_time = getmaxExpectedTime($saverecord->request_session_id);

                    return ApiResponse(200, true, 'success', $saverecord);
                }
            }
            return SimpleResponse(403, false, 'Invalid User Type');
        } catch (\Throwable $th) {
            //throw $th;
            return InternalError($th->getMessage());
        }
    }

    public function StartVideoCalling(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required',
            'customer_mobile' => 'required|numeric',
            'birthDate' => 'required|date',
            'birthtime' => 'required|date_format:h:i:s A',
            'birthPlace' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'expert_id' => 'required|exists:users,id',
            'user_id' => 'required|exists:users,id',
            'is_promonional' => 'required|in:0,1',
            'timezone' => 'required',
            'gender' => 'required',
            'device_type' => 'required'
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        try {
            $requestype = 'video';

            $checkagainavailability = $this->checkuserOnaction($request->expert_id, $request->user_id, $requestype);
            if ($checkagainavailability != true) {

                return ApiResponse(403, true, 'false', $checkagainavailability);
            }
            $checkalreadychat = $this->CheckAlreadyInCall($request->user_id);
            if ($checkalreadychat != 'success') {
                return ApiResponse(403, false, $checkalreadychat);
            }
            CallChatRequest::CancelActiveAllCallChat($request->user_id); // disabale all active chat for this user
            $checkUser = User::select('users.user_type', 'users_details.balance_amount', 'users.id', 'users.image')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $request->user_id)->first();
            $astrodata = $this->getAstroDetails($request->expert_id);
            if ($checkUser->user_type == "USER" && !empty($astrodata)) {
                $CheckAstroBusy = $this->CheckAstroBusy($astrodata->id);
                $request_status = !$CheckAstroBusy ? 20 : 1;
                $asro_charges = !empty($astrodata->disc_video_charge) ? $astrodata->disc_video_charge : $astrodata->astro_video_charges;

                $call_commission = $astrodata->video_commission;
                if ($request->is_promonional == 1) {
                    $asro_charges = 1;
                    $call_commission = 100;
                }
                $chatid = generateConsultId('VID');
                $requestData = [
                    'user_id' => $checkUser->id,
                    'expert_id' => $astrodata->id,
                    'user_name' => $request->fullname,
                    'request_type' => $requestype,
                    'video_commission' => $call_commission,
                    'astro_video_call_charge' => $asro_charges,
                    'waitlist_status' => $request_status == 1 ? 0 : 20, // adding wating count 
                    'form_meta' => serialize($_POST),
                    'device_type' => $request->device_type, // 1 => android ,2 =>ios,
                    'is_promotional' => $request->is_promonional,
                    'request_session_id' => $chatid,
                    'start_session_date' => date("Y-m-d H:i:s", time()),
                    'request_expired' => date("Y-m-d H:i:s", strtotime("+1 day", time())),
                    'request_status' => $request_status,
                    'user_start_time' => date("Y-m-d H:i:s", time()),
                    'new_api'=>1
                ];
                $saverecord = CallChatRequest::create($requestData); /// saving chat request
                if ($saverecord) {
                    $saverecord->expert_details = $astrodata;
                    $user_image = !empty($checkUser->image)
                        ? image_url($checkUser->image,'/public/cms-images/user-images/')
                        : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
                    // $status =$request_status== 2 ?  "active" :"complete" ;
                    $status = "initiate";
                    $saverecord->form_meta = unserialize($saverecord->form_meta);
                    if ($request_status == 1) {


                        FireBaseActionController::AstrologerConsultUpdate($astrodata->id, $checkUser->id, $request->fullname, $user_image, $checkUser->balance_amount, $requestype, $chatid, $status);
                        FireBaseActionController::new_notification_firbase_hits_ivent($requestype, $chatid, $astrodata->id, $request->fullname);
                    }
                    $getfcmtoken = getFcmToken($astrodata->id);
                    if ($getfcmtoken) {
                        // FireBaseActionController::PushNOtification($getfcmtoken,'New Session Request','New Chat request from  '.$request->fullname.'',$user_image,$type='Chat' ?? '');
                        $type = 'video'; // ensure it's defined beforehand
                        FireBaseActionController::PushNOtification($getfcmtoken, 'New Session Request', 'New video call request from  ' . $request->fullname . '', $user_image, $type);
                    }

                    return ApiResponse(200, true, 'success', $saverecord);
                }
            }
            return SimpleResponse(403, false, 'Invalid User Type');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function save_chat(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'messageId' => 'required',
                'room_id' => 'required',
                'status' => 'required',
                'user_id' => 'required|exists:users,id',
                'time' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $getuser = User::find($request->user_id);

            if ($getuser) {
                $getsession = CallChatRequest::where('request_session_id', $request->room_id)->first();
                if ($getsession) {
                    if ($getuser->user_type == 'USER') {
                        $getfcmtoken = getFcmToken($getsession->expert_id);
                        $userdata = User::find($getsession->expert_id);
                    } else {
                        $getfcmtoken = getFcmToken($getsession->user_id);
                        $userdata = User::find($getsession->user_id);
                    }
                    // FireBaseActionController::PushNOtification($getfcmtoken,'New Message',$request->message);
                    $notificationarray = [
                        'title' => 'New Message from ' . $getuser->name . '',
                        'message' => $request->message,
                        'image' => image_url($getuser->image,'/public/cms-images/user-images/'),
                        'type' => 'chat',
                        'senderid' => $getuser->id

                    ];
                    if(!empty($getfcmtoken)){

                        FireBaseActionController::PushNOtificationAuthdata($getfcmtoken, $notificationarray);
                    }
                }
                $time=date('Y-m-d H:i:s',time());

                if(!empty($request->time)){
                    $time=date('Y-m-d H:i:s',strtotime($request->time));
                }
                $user_id = $request->user_id;
                $obj = new ChatMessages();
                $obj->user_id = $user_id;
                $obj->messageId = $request->messageId;
                $obj->message = $request->message;
                $obj->request_session_id = $request->room_id;
                $obj->status = $request->status;
                $obj->time = $time;
                $obj->fileurl = $request->image ? $request->image : '';

                $obj->save();
                    return $notificationarray;

                return ApiResponse(200, true, 'success', $obj);
            } else {
                return SimpleResponse(404, false, 'Invalid user id');
            }
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public static function GetRemedies($consultId)
    {
        try {
            $data = ConsultRemedies::where('consult_it', $consultId)->get();

            if ($data->isNotEmpty()) {
                foreach ($data as $item) {
                    // Handle pooja
                    if (!empty($item->pooja)) {
                        $poojaIds = is_array($item->pooja) ? $item->pooja : json_decode($item->pooja, true);

                        $poojaData = PoojaModel::select('products.*', 'pc.name as category_name')
                            ->join('product_category_maps as pcm', 'pcm.product_id', '=', 'products.id')
                            ->join('product_categories as pc', 'pc.id', '=', 'pcm.category_id')
                            ->where('products.status', 1)
                            ->whereIn('products.id', $poojaIds)
                            ->get()
                            ->transform(function ($poojaItem) {
                                $poojaItem->image_url = $poojaItem->image
                                    ? image_url($poojaItem->image,$poojaItem->image_path)
                                    : asset('cms-images/default/default.jpg');
                                return $poojaItem;
                            });

                        $item->pooja = $poojaData;
                    }

                    // Handle remedies (this part is added)
                    if (!empty($item->remedies)) {
                        $remedies = is_array($item->remedies)
                            ? $item->remedies
                            : json_decode($item->remedies, true);

                        $formattedRemedies = collect($remedies)->map(function ($r) {
                            return [
                                'heading' => $r['heading'] ?? '',
                                'content' => $r['content'] ?? '',
                            ];
                        })->toArray();

                        $item->remedies = $formattedRemedies;
                    } else {
                        $item->remedies = [];
                    }
                }
            }

            if ($data) {
                return ApiResponse(200, true, 'success', $data);
            }
            return SimpleResponse(404, false, 'not found');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function saveSocketToken(Request $request)
    {

        $user_id = $request->user_id;
        $socket_token = $request->socket_token;
        $obj = User::where('id', $user_id)->first();

        if ($obj) {
            $obj->socket_token = $socket_token;
        }
        if ($obj->save()) {
            return ([
                "data" => "socket token saved successfully"
            ]);
        } else {
            return ([
                "data" => "some thing went wrong"
            ]);
        }
    }
    public function getSocketToken(Request $request)
    {

        $user_id = $request->user_id;
        $socket_token = $request->socket_token;
        $obj = User::where('id', $user_id)->first();
        $socket_tokens = $obj->socket_token;
        if ($obj != null) {
            return ([
                "socket_token" => $socket_tokens
            ]);
        } else {
            return ([
                "socket_token" => "some thing went wrong"
            ]);
        }
    }


    public function  SwitchToCallRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'switch_to' => 'required|in:Calling,video',
                'sessionid' => 'required',
                // 'meet_id'=>'required'
            ]);

            // $generateMeetId = getSDKRoomid();
            // if($generateMeetId) {
            //     $meetId = $generateMeetId['meet_id'];
            // }


            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $sessionid = $request->sessionid;

            $getsession = CallChatRequest::where('request_session_id', $sessionid)->first();
            if ($getsession) {
                $checkagainavailability = $this->checkuserOnaction($getsession->expert_id, $getsession->user_id, $request->switch_to);

                if ($checkagainavailability != true) {
                    return ApiResponse(403, true, 'false', $checkagainavailability);
                }

                $checkUser = User::find($getsession->user_id);
                

                $user_image = !empty(!$checkUser->image) ? image_url($checkUser->image,'/public/cms-images/user-images/') : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";

                $astrodetails = User::find($getsession->expert_id);
                $astro_image = !empty(!$astrodetails->image) ? image_url($astrodetails->image,'/public/cms-images/user-images/') : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";


                FireBaseActionController::SwitchSessionEvents($getsession->expert_id, $getsession->user_id, $getsession->user_name, $user_image, $sessionid, 'Initiate', $request->switch_to, $astrodetails->name, $astro_image, '');
                $SwitchSession = SwitchSession::where('session_id', $sessionid)->first();
                if ($SwitchSession) {
                    $SwitchSession->session_id = $sessionid;
                    $SwitchSession->switch_to = $request->switch_to;
                    $SwitchSession->status = $request->status ? $request->status : 0;
                    $SwitchSession->save();
                } else {
                    $SwitchSession = SwitchSession::create([
                        'session_id' => $sessionid,
                        'switch_to' => $request->switch_to,
                        'status' => 0
                    ]);
                }
                return ApiResponse(200, true, 'Success', $SwitchSession);
            }
            return SimpleResponse(404, false, 'Invalid request / Session id');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function  SwitchToCall(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'sessionid' => 'required',
                // 'meet_id'=>'required'
            ]);


            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $sessionid = $request->sessionid;

            $generateMeetId = getSDKRoomid();
            if ($generateMeetId) {
                $meetId = $generateMeetId['meet_id'];
            }

            $getsession = CallChatRequest::where('request_session_id', $sessionid)->first();
            if ($getsession) {
                $checkagainavailability = $this->checkuserOnaction($getsession->expert_id, $getsession->user_id, $request->switch_to);

                if ($checkagainavailability != true) {
                    return ApiResponse(403, true, 'false', $checkagainavailability);
                }
                $SwitchSession = SwitchSession::where('session_id', $sessionid)->where('status', 0)->first();
                if ($SwitchSession) {

                    $checkUser = User::select('users.user_type', 'users_details.balance_amount', 'users.id', 'users.image')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $getsession->user_id)->first();
                    $astrodata = $this->getAstroDetails($getsession->expert_id);
                    if ($checkUser->user_type == "USER" && !empty($astrodata)) {
                        $CheckAstroBusy = $this->CheckAstroBusy($astrodata->id);

                        $request_status = !$CheckAstroBusy ? 20 : 1;
                        $asro_charges = !empty($astrodata->disc_video_charge) ? $astrodata->disc_video_charge : $astrodata->astro_video_charges;
                        $astro_call_charge = !empty($astrodata->disc_call_charge) ? $astrodata->disc_call_charge : $astrodata->astro_call_charges;
                        $call_commission = $astrodata->video_commission;


                        $chatid = $SwitchSession->switch_to == 'Calling' ? generateConsultId('CAL') : generateConsultId('VID');
                        $requestype = $SwitchSession->switch_to;
                        // end previos session 
                        $currentTime = date('Y-m-d H:i:s', time());
                        $callRequestType = $getsession->request_type;
                        $session_id = $sessionid;
                        $expert_id = $getsession->expert_id;
                        $getsession->user_end_time = $currentTime;
                        $getsession->astro_end_time = $currentTime;





                        if ($getsession->total_duration == 0) {
                            $time1 = strtotime($getsession->astro_start_time);
                            $time2 = time();
                            $diff = $time2 - $time1 - 5;
                            $totDuration = getTotalDuration($getsession);

                            $getsession->total_duration = $totDuration;
                            if ($callRequestType == 'Video') {
                                $this->userWalletDebitForCalling($getsession->user_id, $totDuration, $getsession->astro_video_call_charge, $session_id, $expert_id, $callRequestType, $getsession->is_promotional, $getsession->request_status);
                            } elseif ($callRequestType == 'Chat') {
                                $this->userWalletDebitForCalling($getsession->user_id, $totDuration, $getsession->astro_chat_charge, $session_id, $expert_id, $callRequestType, $getsession->is_promotional, $getsession->request_status);
                            } else {
                                $this->userWalletDebitForCalling($getsession->user_id, $totDuration, $getsession->astro_call_chagre, $session_id, $expert_id, $callRequestType, $getsession->is_promotional, $getsession->request_status);
                            }
                        }
                        if (in_array($getsession->request_status, [1, 2])) {
                            $getsession->request_status = $getsession->request_status == 1 ? 7 : 5;
                            $getsession->request_status_log = $getsession->request_status == 7 ? 'Cancelled by User' : 'Ended by User';
                        }
                        $getsession->save();



                        // end previous session  
                        $requestData = [
                            'user_id' => $checkUser->id,
                            'expert_id' => $astrodata->id,
                            'user_name' => $getsession->user_name,
                            'request_type' => $requestype,
                            'video_commission' => $call_commission,
                            'astro_call_chagre' => $astro_call_charge,
                            'astro_video_call_charge' => $asro_charges,
                            'waitlist_status' => $request_status == 1 ? 0 : 20, // adding wating count 
                            'form_meta' => $getsession->form_meta,
                            'device_type' => $getsession->device_type, // 1 => android ,2 =>ios,
                            'is_promotional' => 0,
                            'request_session_id' => $chatid,
                            'start_session_date' => date("Y-m-d H:i:s", time()),
                            'request_expired' => date("Y-m-d H:i:s", strtotime("+1 day", time())),
                            'request_status' => 2,
                            'user_start_time' => date("Y-m-d H:i:s", time()),
                            'astro_start_time' => date("Y-m-d H:i:s", time()),

                        ];
                        $saverecord = CallChatRequest::create($requestData); /// saving chat request
                        $saverecord->expert_details = $astrodata;
                        $user_image = !empty(!$checkUser->image) ? "https://astro-api.iqsetters.in/public/cms-images/user-images/$checkUser->image" : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
                        $status = "active";
                        $saverecord->max_end_time = getmaxExpectedTime($saverecord->request_session_id);

                        if ($saverecord->max_end_time['status']) {
                            $end_time = $saverecord->max_end_time['data']['max_time'];
                        } else {
                            $end_time = '';
                        }

                        UsersDetail::where('user_id', $astrodata->id)->update(['availability' => 2]);

                        $saverecord->form_meta = unserialize($saverecord->form_meta);
                        FireBaseActionController::new_notification_firbase_hits_ivent($requestype, $chatid, $astrodata->id, $getsession->user_name);
                        FireBaseActionController::AstrologerConsultUpdate($astrodata->id, $checkUser->id, $getsession->user_name, $user_image, $checkUser->balance_amount, $requestype, $chatid, $status, $saverecord->astro_start_time, $end_time);


                        FireBaseActionController::SwitchSessionEvents($astrodata->id, $checkUser->id, $getsession->user_name, $user_image, $sessionid, 'Complete', $request->switch_to, $astrodata->name, $astrodata->image, $meetId, $saverecord->request_session_id);
                        $saverecord->image = $user_image;
                        $SwitchSession->status = 1;
                        $SwitchSession->save();


                        return ApiResponse(200, true, 'success', $saverecord);
                    }
                    return SimpleResponse(403, false, 'Invalid User Type');
                }
                return SimpleResponse(404, false, 'Unaothorized');
            }
            return SimpleResponse(404, false, 'Invalid request / Session id');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function checkflagavaibility(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'switch_to' => 'required',
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        try {
            $checksession = CallChatRequest::where('request_session_id', $request->sessionid)->first();

            if ($checksession) {

                $userdata = User::select('users.id', 'users.name', 'users_details.balance_amount')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $checksession->user_id)->first();

                if ($userdata) {


                    $astrodata = $this->getAstroDetails($checksession->expert_id);
                    if ($astrodata) {


                        $flags = explode(',', $astrodata->flags);

                        if ($astrodata->availability == 0) {
                            return errorResponse(genererateErrorFields('expert_id', $astrodata->name . ' is offline'), 'failed');
                        } elseif (!in_array($request->switch_to, $flags)) {
                            return ApiResponse(403, false, $astrodata->name . ' does not accept ' . $request->switch_to);
                        } else if ($astrodata->balance_amount > 0) {

                            if ($request->switch_to == 'video') {
                                $asro_charges = !empty($astrodata->disc_video_charge) ? $astrodata->disc_video_charge : $astrodata->astro_video_charges;
                            } else {
                                $asro_charges = !empty($astrodata->disc_call_charge) ? $astrodata->disc_call_charge : $astrodata->astro_call_charges;
                            }

                            $astroFiveMinCharge = ($asro_charges * 5);
                            if ($astroFiveMinCharge > $userdata->balance_amount) {
                                return SimpleResponse(
                                    201,
                                    false,
                                    'You need minimum balance of 5 minutes, ₹' . $astroFiveMinCharge . ' is required in your wallet to start chat with ' . $astrodata->name . '.'
                                );
                            }
                        } else {
                            return ApiResponse(200, true, 'Success');
                        }
                    } else {
                        return SimpleResponse(404, false, 'Invalid user or astrologer');
                    }
                }
                return SimpleResponse(404, false, 'Invalid user or astrologer');
            } else {
                return SimpleResponse(404, false, 'Invalid session');
            }
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }




    public function checAstroService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expert_id' => 'required',
            'switch_to' => 'required|in:chat,call,video'
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }

        $astrodata = $this->getAstroDetails($request->expert_id);

        if ($astrodata) {

            $flags = explode(',', $astrodata->flags);

            if ($astrodata->availability == 0) {
                return errorResponse(genererateErrorFields('expert_id', $astrodata->name . ' is offline'), 'failed');
            } elseif (!in_array($request->switch_to, $flags)) {
                return ApiResponse(403, false, $astrodata->name . ' does not accept ' . $request->switch_to);
            } else {
                return ApiResponse(200, true, 'success', $astrodata->flags);
            }
        } else {
            return SimpleResponse(404, false, 'Invalid user or astrologer');
        }
    }


    public function MarkChatRead(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'chat_id' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $updateChatseen = ChatMessages::where('chat_id', $request->chat_id)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
            return ApiResponse(200, true, 'success', $updateChatseen);
        } catch (\Throwable $th) {
        }
    }

    public function JoinWaitlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required',
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        $checksession = CallChatRequest::where('request_session_id', $request->session_id)
            ->where('request_status', 20)
            ->first();
        $checkUser = User::select('users.user_type', 'users_details.balance_amount', 'users.id', 'users.image')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $checksession->user_id)->first();
        if ($checksession && $checkUser) {
            $requestype = $checksession->request_type;
            $checkagainavailability = $this->checkuserOnaction($checksession->expert_id, $checksession->user_id, $requestype);
            if ($checkagainavailability != true) {

                return ApiResponse(403, true, 'false', $checkagainavailability);
            }

            $astrodata = $this->getAstroDetails($checksession->expert_id);
            if ($checkUser->user_type == "USER" && !empty($astrodata)) {
                $CheckAstroBusy = $this->CheckAstroBusy($astrodata->id);
                $request_status = 1; //active
                $asro_charges = !empty($astrodata->disc_chat_charge) ? $astrodata->disc_chat_charge : $astrodata->astro_chat_charges;
                $chat_commission = $astrodata->chat_commission;
                if ($checksession->is_promotional == 1) {
                    $asro_charges = 1;
                    $chat_commission = 100;
                }

                $updateExpertStatus = UsersDetail::where('user_id', @$checksession->expert_id)->first();
                $updateExpertStatus->availability = 2; // set expert bust
                $updateExpertStatus->save();
                $currentTime = date('Y-m-d H:i:s', time());
                $checksession->astro_start_time = $currentTime;

                if ($requestype == 'Calling') {
                    $status = "initiate";
                    $checksession->request_status = 1;
                } else {
                    $status = "active";
                    $checksession->request_status = 2;
                }

                $checksession->save();
                $chatid = $checksession->request_session_id;

                $checksession->expert_details = $astrodata;
                $user_image = !empty(!$checkUser->image) ? "https://astro-api.iqsetters.in/public/cms-images/user-images/$checkUser->image" : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
                // $status =$request_status== 2 ?  "active" :"complete" ;
                $checksession->form_meta = unserialize($checksession->form_meta);



                if (!empty($checksession->form_meta['time_zone']) && !empty($checksession->form_meta['long']) && !empty($checksession->form_meta['lat']) && !empty($checksession->form_meta['place']) && !empty($checksession->form_meta['birth_date']) && !empty($checksession->form_meta['birth_time']) && !empty($checksession->form_meta['gender'])) {
                    $requestdata = [
                        'full_name' => $checksession->user_name,
                        'birth_date' => $checksession->form_meta['birth_date'],
                        'birth_time' => Carbon::parse($checksession->form_meta['birth_time']),
                        'gender' => $checksession->form_meta['gender'],
                        'place' => $checksession->form_meta['place'],
                        'lat' => $checksession->form_meta['lat'],
                        'long' => $checksession->form_meta['long'],
                        'time_zone' => $checksession->form_meta['time_zone'],
                        'lang' => 'en',
                    ];

                    $checksession->report = FreeKundaliController::GeneralReport($requestdata);
                }
                $checksession->max_end_time = getmaxExpectedTime($checksession->request_session_id);

                if ($checksession->max_end_time['status']) {
                    $end_time = $checksession->max_end_time['data']['max_time'];
                } else {
                    $end_time = '';
                }

                $user_image = !empty(!$checkUser->image) ? "https://astro-api.iqsetters.in/public/cms-images/user-images/$checkUser->image" : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
                FireBaseActionController::new_notification_firbase_hits_ivent($requestype, $chatid, $astrodata->id, $checksession->user_name);

                if($requestype !='Calling'){
                    try {
                    $response = Http::post(
                        env('SOCKET_SERVER_URL', 'http://localhost:65282') . '/update-server-timer',
                        [
                            'room'    => $chatid,
                            'endTime' => $end_time
                        ]
                    );

                        // Call only if API response is successful (200–299)
                        if ($response->successful()) {
                            FireBaseActionController::AstrologerConsultUpdate($astrodata->id, $checkUser->id, $checksession->user_name, $user_image, $checkUser->balance_amount, $requestype, $chatid, $status, $currentTime, $end_time);

                        } else {
                            \Log::error('Socket API failed', [
                                'status' => $response->status(),
                                'body'   => $response->body()
                            ]);
                        }

                        } catch (\Throwable $e) {
                            \Log::error("Socket Timer Init Failed: " . $e->getMessage());
                        }
                }else{
                 FireBaseActionController::AstrologerConsultUpdate($astrodata->id, $checkUser->id, $checksession->user_name, $user_image, $checkUser->balance_amount, $requestype, $chatid, $status, $currentTime, $end_time);

                }

                 




                return ApiResponse(200, true, 'success', $checksession);
            }

            // FireBaseActionController::AstrologerConsultUpdate($checksession->expert_id,$checksession->user_id,$checksession->user_name,$user_image,$checkUser->balance_amount,$checksession->request_type,$checksession->request_session_id,'join_waitlist_initiate','');
            return ApiResponse(200, true, 'Success', $checksession);
        }
        return ApiResponse(404, false, 'invalid session / User id ');
    }



    function getOngoingSession(Request $request)
    {
        try {
            $authid = $request->auth_user->id;

            $loggedInUser = User::select('user_type')->where('id', $authid)->first();

            if (!$loggedInUser) {
                return ApiResponse(404, false, 'User not found');
            }

            $getdata = CallChatRequest::join('mst_order_status', 'mst_order_status.order_status_id', '=', 'call_chat_request.request_status')
                ->join('users', 'users.id', '=', 'call_chat_request.expert_id')
               
                 ->leftJoin(
        'users as userdata',
        'userdata.id',
        '=',
        'call_chat_request.user_id'
    )

                ->select(
                    'call_chat_request.*',
                    'mst_order_status.name as status_name',
                    'users.name',
                    'userdata.image as image',
                    'users.user_type'
                )
                ->where(function ($q) use ($authid, $loggedInUser) {
                    if ($loggedInUser->user_type === 'USER') {
                        $q->where('call_chat_request.user_id', $authid);
                    } else {
                        $q->where('call_chat_request.expert_id', $authid);
                    }
                })
                ->whereIn('request_status', [20, 1, 2])
                ->first();

            if (!$getdata) {
                return ApiResponse(404, false, 'No ongoing session found');
            }

            $astrodata = $this->getAstroDetails($getdata->expert_id);

            if ($getdata->request_status == 2) {
                $getdata->max_end_time = getmaxExpectedTime($getdata->request_session_id);
            } elseif ($getdata->request_status == 20) {
                //  $getdata->wait_time = getWaitingTimeshort($getdata->expert_id);
                //$getdata->wait_time = getWaitingTimeshortSingle($getdata->expert_id);
                $getdata->wait_time = getWaitingTimeshortSingle($getdata->expert_id, $authid); // ✅ pass user ID too


            }

            if ($getdata->request_status == 20 && strtolower($getdata->status_name) == 'waitlist') {
                $getdata->status_name = 'join_waitlist_initiate';
            }

            $getdata->expert_details = $astrodata;
            $getdata->form_meta = unserialize($getdata->form_meta);
            $getdata->socket_url = env('SOCKET_SERVER_URL');

            $getdata->image = !empty($getdata->image)
                ? image_url($getdata->image,'/public/cms-images/user-images/')
                : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";

            return ApiResponse(200, true, 'fetch successfully', $getdata);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }


    public function ChangeMessageStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message_id' => 'required',
                'status' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $updateChatseen = ChatMessages::where('messageId', $request->message_id)
                ->update(['status' => $request->status]);
            return ApiResponse(200, true, 'success', $updateChatseen);
        } catch (\Throwable $th) {
                 return InternalError($th->getMessage());
        }
    }

    public function getSessionData($sessionid)
    {
        try {
            $getdata = CallChatRequest::where('request_session_id', $sessionid)->first(); /// saving chat request
            if ($getdata) {
                $astrodata = $this->getAstroDetails($getdata->expert_id);

                $getdata->expert_details = $astrodata;
                $getdata->form_meta = unserialize($getdata->form_meta);
            }

            return ApiResponse(200, true, 'success', $getdata);
        } catch (\Throwable $th) {
                return InternalError($th->getMessage());
        }
    }

    public function getconsultreview(Request $request)
    {
        try {
            
        $consultId = $request->consult_id;

        if (!$consultId) {
            return response()->json([
                'statusCode' => 400,
                'status'     => false,
                'message'    => 'consult_id is required.'
            ]);
        }

        $review = DB::table('review')
            ->leftJoin('users', 'users.id', '=', 'review.user_id')
            ->where('review.consult_id', $consultId)
            ->orderByDesc('review.id')
            ->limit(1)
            ->select(
                'review.*',
                'users.name as user_name',
                'users.image as user_image'
            )
            ->first();

        if ($review) {
            // Custom user image path with fallback
            $review->user_image = !empty($review->user_image)
                ?  image_url($review->image,'/public/cms-images/user-images/')
                : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";

            return response()->json([
                'statusCode' => 200,
                'status'     => true,
                'message'    => 'Review found.',
                'data'       => $review
            ]);
        }

        return response()->json([
            'statusCode' => 404,
            'status'     => false,
            'message'    => 'No review found for this consult_id.'
        ]);
        } catch (\Throwable $th) {
            //throw $th;
                return InternalError($th->getMessage());
        }
    }
}
