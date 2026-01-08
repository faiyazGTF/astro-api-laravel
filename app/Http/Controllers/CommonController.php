<?php

namespace App\Http\Controllers;

use App\Models\AstroConferencing;
use App\Models\CallChatRequest;
use App\Models\User\User;
use App\Models\User\UsersDetail;
use App\Models\User\WalletsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\PoojaModel;
use Firebase\JWT\JWT;
use DateTimeImmutable;
use Illuminate\Support\Facades\Storage;

class CommonController extends Controller
{
    //
    public function languages(Request $request)
    {
        try {
            $data = DB::table('mst_languages')->get();
            return  ApiResponse(200, true, 'success', $data);
        } catch (\Throwable $th) {
            return  InternalError($th->getMessage());
        }
    }
    public function uploadfile(Request $request)
    {
        try {

            // Validate Image
            $validator = Validator::make($request->all(), [
                "file" => "required|file",
                "foldername" => "required"
            ]);

            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $foldername = $request->foldername;

            $file = $request->file("file");
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $fileExt = $file->extension();
            $fileName = rand() . "_" . Str::slug($originalName) . "." . $fileExt;
            $result = Storage::disk('s3')->putFileAs('public/other-files/' . $foldername, $file, $fileName);
            $filename = image_url($fileName, '/public/other-files/' . $foldername . '/');
            return  ApiResponse(200, true, 'success', $filename);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function firestore_update_for_cron($current_session_id)
    {
        $time = date('Y-m-d\TH:i:s.v\Z');
        $current_session_id = $current_session_id;
        $active_consult = CallChatRequest::where("request_session_id", $current_session_id)->first();
        $astro_id = $active_consult->user_id;
        if (@$active_consult->request_status == 2) {
            $status = "active";
        } else {
            $status = "Complete";
        }
        @$user_id = $active_consult->user_id;
        $user = User::leftjoin('users_details', 'users_details.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.image', 'users_details.balance_amount')
            ->where('users.id', $user_id)->first();

        if (@$user->image != "") {
            $image = image_url($user->image, '/public/cms-images/user-images/');
        } else {
            $image = "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
        }
        $curl = curl_init();
        $url = "https://firestore.googleapis.com/v1/projects/astroeranew/databases/(default)/documents/astrologer_consult/$astro_id?updateMask.fieldPaths=user_profile&updateMask.fieldPaths=start_time&updateMask.fieldPaths=total_waitlist_count&updateMask.fieldPaths=user_name&updateMask.fieldPaths=consult_id&updateMask.fieldPaths=user_wallet_balance&updateMask.fieldPaths=user_id&updateMask.fieldPaths=consult_type&updateMask.fieldPaths=consult_status";
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => '
                {
                    "name": "projects/astroeranew/databases/(default)/documents/astrologer_consult/' . $astro_id . '",
                    "fields": {
                        "user_profile": {
                            "stringValue": "' . $image . '"
                        },
                        "start_time": {
                            "stringValue": "' . @$active_consult->astro_start_time . '"
                        },
                        "total_waitlist_count": {
                            "integerValue": "0"
                        },
                        "user_name": {
                            "stringValue": "' . @$user->name . '"
                        },
                        "consult_id": {
                            "stringValue": "' . @$active_consult->request_session_id . '"
                        },
                        "user_wallet_balance": {
                            "integerValue": "' . (@$user->balance_amount ? @$user->balance_amount : 0) . '"
                        },
                        "user_id": {
                            "stringValue": "' . @$user->id . '"
                        },
                        "consult_type": {
                            "stringValue": "' . @$active_consult->request_type . '"
                        },
                        "consult_status": {
                            "stringValue": "' . @$status . '"
                        }
                    },
                    "createTime": "' . $time . '",
                    "updateTime": "' . $time . '"
                   }
                 ',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //echo $response;


    }

    public function firestore_update_for_cron_astro($current_session_id)
    {

        $time = date('Y-m-d\TH:i:s.v\Z');

        $current_session_id = $current_session_id;

        $active_consult = CallChatRequest::where("request_session_id", $current_session_id)->first();

        $astro_id = $active_consult->expert_id;

        if (@$active_consult->request_status == 2) {
            $status = "active";
        } else {
            $status = "Cancelled";
        }

        @$user_id = $active_consult->user_id;

        $user = User::leftjoin('users_details', 'users_details.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.image', 'users_details.balance_amount')
            ->where('users.id', $user_id)->first();

        if (@$user->image != "") {
            $image = image_url($user->image, '/public/cms-images/user-images/');
        } else {
            $image = "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
        }



        $curl = curl_init();
        $url = "https://firestore.googleapis.com/v1/projects/astroeranew/databases/(default)/documents/astrologer_consult/$astro_id?updateMask.fieldPaths=user_profile&updateMask.fieldPaths=start_time&updateMask.fieldPaths=total_waitlist_count&updateMask.fieldPaths=user_name&updateMask.fieldPaths=consult_id&updateMask.fieldPaths=user_wallet_balance&updateMask.fieldPaths=user_id&updateMask.fieldPaths=consult_type&updateMask.fieldPaths=consult_status";


        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => '
                {
                    "name": "projects/astroeranew/databases/(default)/documents/astrologer_consult/' . $astro_id . '",
                    "fields": {
                        "user_profile": {
                            "stringValue": "' . $image . '"
                        },
                        "start_time": {
                            "stringValue": "' . @$active_consult->astro_start_time . '"
                        },
                        "total_waitlist_count": {
                            "integerValue": "0"
                        },
                        "user_name": {
                            "stringValue": "' . @$user->name . '"
                        },
                        "consult_id": {
                            "stringValue": "' . @$active_consult->request_session_id . '"
                        },
                        "user_wallet_balance": {
                            "integerValue": "' . (@$user->balance_amount ? @$user->balance_amount : 0) . '"
                        },
                        "user_id": {
                            "stringValue": "' . @$user->id . '"
                        },
                        "consult_type": {
                            "stringValue": "' . @$active_consult->request_type . '"
                        },
                        "consult_status": {
                            "stringValue": "' . @$status . '"
                        }
                    },
                    "createTime": "' . $time . '",
                    "updateTime": "' . $time . '"
                   }
                 ',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //echo $response;


    }


    public function endsession(Request $request)
    {

        try {

            return;
            exit;


            $present_time = now();

            $initiatedSession = CallChatRequest::where('request_status', 2)->get();



            foreach ($initiatedSession as $initsession) {
                $userID = $initsession->user_id;

                $datenew = strtotime($initsession->astro_start_time);
                $astroStartTime = date('Y-m-d H:i:s', $datenew);

                if ($initsession->is_promotional !== 1) {
                    if ($initsession->request_type == 'Calling') {
                        $astrocharge = $initsession->astro_call_chagre;
                    } else if ($initsession->request_type == 'Video') {
                        $astrocharge = $initsession->astro_video_call_charge;
                    } else {
                        $astrocharge = $initsession->astro_chat_charge;
                    }

                    $user = UsersDetail::where('user_id', $userID)->first();

                    $userBalance = $user->balance_amount;



                    $duration = +floor($userBalance / $astrocharge) * 60;

                    $durationinminute = floor($userBalance / $astrocharge); // max minute chat 


                    $durationinsec = $durationinminute * 60;

                    //  }



                    $endDuration = date("Y-m-d H:i:s", strtotime("$durationinsec second", strtotime($astroStartTime)));


                    $sessiontime = $present_time->diffInMinutes($endDuration, false);

                    $timediff = $sessiontime * 60;


                    if ($timediff <= 10) {


                        CallChatRequest::where('request_session_id', $initsession->request_session_id)->update(['request_status_log' => 'Ended by Cron', 'request_status' => '5', 'astro_end_time' => $endDuration, 'total_duration' => $durationinsec]);

                        $astroDetails = UsersDetail::where('user_id', $initsession->expert_id)->first();
                        if ($initsession->request_type == 'Chat') {
                            $call_astro_commission = $astroDetails->chat_commission;
                        } else {
                            $call_astro_commission = $astroDetails->call_commission;
                        }



                        if ($call_astro_commission > 0) {
                            $astroPayAmount = (int) ($astrocharge * $call_astro_commission) / 100;
                            $astroCallCast = $astroPayAmount * $durationinminute;
                        } else {
                            $astroPayAmount = 0;
                            $astroCallCast = $astroPayAmount * $durationinminute;
                        }

                        $astroBal = $astroDetails->balance_amount + $astroCallCast;
                        $astroDetails->balance_amount = $astroBal;
                        $astroDetails->availability = 1;
                        $astroDetails->save();

                        $update_store = $this->firestore_update_for_cron($initsession->request_session_id);
                        $update_store_for_astro = $this->firestore_update_for_cron_astro($initsession->request_session_id);

                        $entry = WalletsModel::where('transaction_id', $initsession->request_session_id)->where('transaction_type', 'credits')->first();

                        if ($entry == null) {

                            $expertWalObj = new WalletsModel();
                            $expertWalObj->user_id = $initsession->expert_id;
                            $expertWalObj->transaction_id = $initsession->request_session_id;
                            $expertWalObj->transaction_type = 'credits';
                            $expertWalObj->product_type = Str::lower($initsession->request_type);
                            $expertWalObj->transaction_by = 'user';
                            $expertWalObj->balance_amount = $astroBal;
                            $expertWalObj->amount = $astroCallCast;
                            if ($expertWalObj->save()) {
                                $astroDetails->save();
                            }

                            $userWalObj = new WalletsModel();
                            $userWalObj->user_id = $initsession->user_id;
                            $userWalObj->transaction_id = $initsession->request_session_id;
                            $userWalObj->transaction_type = 'debits';
                            $userWalObj->product_type = Str::lower($initsession->request_type);
                            $userWalObj->transaction_by = 'user';
                            $userWalObj->balance_amount = $userBalance - ($astrocharge * $durationinminute);
                            $userWalObj->amount = $astrocharge * $durationinminute;
                            $userWalObj->save();

                            DB::table('user_transaction_ledeger')->insert([
                                'usertype' => 'user',
                                'transaction_id' => $initsession->request_session_id,
                                'user_id' => $initsession->user_id,
                                'current_balance' => $userBalance,
                                'new_balance' => $userBalance - ($astrocharge * $durationinminute),
                                'created_at' => date('Y-m-d H:i:s', time()),
                                'top_up_balance' => $astrocharge * $durationinminute,
                                'conditions' => 'c1',
                                'transaction_type' => 'debits',
                            ]);
                        } else {
                            WalletsModel::where('transaction_id', $initsession->request_session_id)->where('transaction_type', 'credits')->update(['amount' => $astroCallCast, 'balance_amount' => $astroBal]);
                            WalletsModel::where('transaction_id', $initsession->request_session_id)->where('transaction_type', 'debits')->update(['amount' => ($astrocharge * $durationinminute), 'balance_amount' => $userBalance - ($astrocharge * $durationinminute)]);
                        }

                        UsersDetail::where('user_id', $userID)->update(['balance_amount' => $userBalance - ($astrocharge * $durationinminute)]);
                    }
                } else {
                    if ($initsession->request_type == 'Call') {
                        $astrocharge = $initsession->astro_call_chagre;
                    } else if ($initsession->request_type == 'Video') {
                        $astrocharge = $initsession->astro_video_call_charge;
                    } else {
                        $astrocharge = $initsession->astro_chat_charge;
                    }

                    $sessiontime = $present_time->diffInMinutes($astroStartTime);

                    $endDuration = date("Y-m-d H:i:s", strtotime("240 second", strtotime($astroStartTime)));
                    // $endDuration = date_add($astroStartTime,date_interval_create_from_date_string("300 second"));

                    if ($sessiontime >= 4) {
                        CallChatRequest::where('request_session_id', $initsession->request_session_id)->update(['request_status_log' => 'Ended by Cron', 'request_status' => '5', 'astro_end_time' => $endDuration, 'total_duration' => 180]);

                        $astroDetails = UsersDetail::where('user_id', $initsession->expert_id)->first();

                        $astroBal = $astroDetails->balance_amount;
                        $astroDetails->balance_amount = $astroBal;
                        $astroDetails->availability = 1;
                        $astroDetails->save();

                        $update_store = $this->firestore_update_for_cron($initsession->request_session_id);
                        $update_store_for_astro = $this->firestore_update_for_cron_astro($initsession->request_session_id);

                        $entry = WalletsModel::where('transaction_id', $initsession->request_session_id)->where('transaction_type', 'credits')->first();

                        //   dd($entry);
                        if ($entry == null) {

                            $expertWalObj = new WalletsModel();
                            $expertWalObj->user_id = $initsession->expert_id;
                            $expertWalObj->transaction_id = $initsession->request_session_id;
                            $expertWalObj->transaction_type = 'credits';
                            $expertWalObj->product_type = Str::lower($initsession->request_type);
                            $expertWalObj->transaction_by = 'user';
                            $expertWalObj->balance_amount = $astroBal;
                            $expertWalObj->amount = 0;
                            if ($expertWalObj->save()) {
                                $astroDetails->save();
                            }

                            $userWalObj = new WalletsModel();
                            $userWalObj->user_id = $initsession->user_id;
                            $userWalObj->transaction_id = $initsession->request_session_id;
                            $userWalObj->transaction_type = 'debits';
                            $userWalObj->product_type = Str::lower($initsession->request_type);
                            $userWalObj->transaction_by = 'user';
                            $userWalObj->balance_amount = 0;
                            $userWalObj->amount = 0;
                            $userWalObj->save();


                            DB::table('user_transaction_ledeger')->insert([
                                'usertype' => 'user',
                                'transaction_id' => $initsession->request_session_id,
                                'user_id' => $initsession->user_id,
                                'current_balance' => 0,
                                'new_balance' => 0,
                                'created_at' => date('Y-m-d H:i:s', time()),
                                'top_up_balance' => 0,
                                'conditions' => 'c2',
                                'transaction_type' => 'debits',
                            ]);
                        } else {
                            WalletsModel::where('transaction_id', $initsession->request_session_id)->where('transaction_type', 'credits')->update(['amount' => 0, 'balance_amount' => $astroBal]);
                            WalletsModel::where('transaction_id', $initsession->request_session_id)->where('transaction_type', 'debits')->update(['amount' => 0, 'balance_amount' => 0]);
                        }
                    }
                }
            }

            return ([
                "msg" => "chat ended",

            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public function conferencingAstro(Request $request)
    {
        try {
            $data = AstroConferencing::join('users', 'users.id', '=', 'astro_conferencing.astro_id')
                ->join('users_details', 'users_details.user_id', '=', 'users.id') // Fixed join condition
                ->leftJoin('mst_countries', 'mst_countries.id', '=', 'users_details.country')
                ->leftJoin('mst_states', 'mst_states.id', '=', 'users_details.state')
                ->leftJoin('mst_cities', 'mst_cities.id', '=', 'users_details.city')
                ->leftJoin('mst_languages', 'mst_languages.id', '=', 'users_details.languages')
                ->select(
                    'astro_conferencing.roomid',
                    'astro_conferencing.is_available',
                    'astro_conferencing.token',
                    'users.id',
                    'users.name',
                    'users.mobile',
                    'users.email',
                    'users.user_type',
                    'users.created_at',
                    'users.image',
                    'users_details.*',
                    'users.name as profile_name_en',
                    'mst_countries.country_name',
                    'mst_states.state_name',
                    'mst_cities.city_name'
                )
                ->where('is_available', 1) // Fixed where condition
                ->paginate(10)
                ->map(function ($item) {
                    $item->image = image_url($item->image, '/public/cms-images/user-images/');
                    return $item;
                });; // Added limit for pagination

            return  ApiResponse(200, true, 'success', $data);
        } catch (\Throwable $th) {
            return  InternalError($th->getMessage());
        }
    }
    public function global_search(Request $request)
    {

        $q = $request->search;
        $type = $request->type;


        $list_users_ids = [20];

        if ($type == "pooja" || $type == "global") {
            $result = PoojaModel::where('name', 'like', '%' . "$q" . '%')
                ->where('product_type', '!=', 'Product')
                ->get()
                ->transform(function ($item) {
                    $item->image = image_url($item->image, $item->image_path);
                });
        }

        if ($type == "call" || $type == "chat" || $type == "video") {


            $ast = User::leftjoin('users_details', 'users_details.user_id', '=', 'users.id')
                ->where('users.name', 'like', '%' . "$q" . '%')
                ->where('users.is_signup_complete', 1)
                ->where('users.astroera_account', 0)
                ->where('users.status', 1)
                ->where('users.user_type', 'ASTROLOGER')
                ->whereRaw('FIND_IN_SET(?, users_details.flags) > 0', [$type])
                ->get();

            $astro = array();

            foreach ($ast as $astr) {

                $astroos['id'] = $astr->id;
                $astroos['name'] = $astr->name;
                $astroos['email'] = $astr->email;
                $astroos['country_code'] = $astr->country_code;
                $astroos['mobile'] = $astr->mobile;
                $astroos['status'] = $astr->status;
                $astroos['user_type'] = $astr->user_type;

                $astroos['referral_id'] = $astr->referral_id;
                $astroos['email_verification_token'] = $astr->email_verification_token;
                $astroos['email_verified_at'] = $astr->email_verified_at;
                $astroos['is_signup_complete'] = $astr->is_signup_complete;
                $astroos['created_at'] = $astr->created_at;
                $astroos['updated_at'] = $astr->updated_at;
                $astroos['firebase_tokens'] = $astr->firebase_tokens;
                $astroos['socket_token'] = $astr->socket_token;
                $astroos['astroera_account'] = $astr->astroera_account;
                $astroos['is_deleted'] = $astr->is_deleted;
                $astroos['user_id'] = $astr->user_id;
                $astroos['mobile2'] = $astr->mobile2;
                $astroos['profile_name_en'] = $astr->profile_name_en;
                $astroos['profile_name_hn'] = $astr->profile_name_hn;
                $astroos['gender'] = $astr->gender;
                $astroos['birth_place'] = $astr->birth_place;
                $astroos['dob'] = $astr->dob;
                $astroos['rating'] = $astr->rating;
                $astroos['slug'] = $astr->slug;
                $astroos['about_me_hn'] = $astr->about_me_hn;
                $astroos['about_me_en'] = $astr->about_me_en;
                $astroos['specialisation'] = $astr->specialisation;
                $astroos['all_skills'] = $astr->all_skills;
                $astroos['languages'] = $astr->languages;
                $astroos['experience'] = $astr->experience;
                $astroos['country'] = $astr->country;
                $astroos['state'] = $astr->state;
                $astroos['city'] = $astr->city;
                $astroos['address'] = $astr->address;
                $astroos['zip_code'] = $astr->zip_code;
                $astroos['is_login'] = $astr->is_login;
                $astroos['availability'] = $astr->availability;
                $astroos['astro_call_charges'] = $astr->astro_call_charges;
                $astroos['astro_chat_charges'] = $astr->astro_chat_charges;
                $astroos['call_commission'] = $astr->call_commission;
                $astroos['chat_commission'] = $astr->chat_commission;
                $astroos['disc_call_charge'] = $astr->disc_call_charge;
                $astroos['disc_chat_charge'] = $astr->disc_chat_charge;
                $astroos['astro_video_charges'] = $astr->astro_video_charges;
                $astroos['video_commission'] = $astr->video_commission;
                $astroos['disc_video_charge'] = $astr->disc_video_charge;
                $astroos['gift_commission'] = $astr->gift_commission;
                $astroos['flags'] = $astr->flags;
                $astroos['top_10s'] = $astr->top_10s;
                $astroos['device_id'] = $astr->device_id;
                $astroos['image_path'] = $astr->image_path;
                $astroos['user_token'] = $astr->user_token;
                $astroos['token_expiry'] = $astr->token_expiry;
                $astroos['user_otp'] = $astr->user_otp;
                $astroos['otp_expire'] = $astr->otp_expire;
                $astroos['balance_amount'] = $astr->balance_amount;
                $astroos['promo_balance'] = $astr->promo_balance;
                $astroos['last_rechages'] = $astr->last_rechages;
                $astroos['last_recharge_at'] = $astr->last_recharge_at;
                $astroos['aadhar_doc'] = $astr->aadhar_doc;
                $astroos['profile_image'] = $astr->profile_image;
                $astroos['is_promotional_assign'] = $astr->is_promotional_assign;
                $astroos['is_promotional_accept'] = $astr->is_promotional_accept;
                $astroos['label'] = $astr->label;
                $astroos['celebrity_astro'] = $astr->celebrity_astro;
                $astroos['address_details'] = $astr->address_details;
                $astroos['is_updated'] = $astr->is_updated;


                $astroos['image'] = image_url($astr->image, '/public/cms-images/user-images/');
                $astroos['skills'] = implode(',', explodespecialization($astr->specialisation, 'en'));
                $astroos['language_name'] = explodesLanguage($astr->languages);

                $astro[] = $astroos;
            }
        } else if ($type == "global") {


            $ast = User::leftjoin('users_details', 'users_details.user_id', '=', 'users.id')
                ->where('users.name', 'like', '%' . "$q" . '%')
                ->where('users.is_signup_complete', 1)
                ->where('users.status', 1)
                ->where('users.astroera_account', 0)
                ->where('users.user_type', 'ASTROLOGER')
                ->get();
            $astro = array();

            foreach ($ast as $astr) {

                $astroos['id'] = $astr->id;
                $astroos['name'] = $astr->name;
                $astroos['email'] = $astr->email;
                $astroos['country_code'] = $astr->country_code;
                $astroos['mobile'] = $astr->mobile;
                $astroos['status'] = $astr->status;
                $astroos['user_type'] = $astr->user_type;

                $astroos['referral_id'] = $astr->referral_id;
                $astroos['email_verification_token'] = $astr->email_verification_token;
                $astroos['email_verified_at'] = $astr->email_verified_at;
                $astroos['is_signup_complete'] = $astr->is_signup_complete;
                $astroos['created_at'] = $astr->created_at;
                $astroos['updated_at'] = $astr->updated_at;
                $astroos['firebase_tokens'] = $astr->firebase_tokens;
                $astroos['socket_token'] = $astr->socket_token;
                $astroos['astroera_account'] = $astr->astroera_account;
                $astroos['is_deleted'] = $astr->is_deleted;
                $astroos['user_id'] = $astr->user_id;
                $astroos['mobile2'] = $astr->mobile2;
                $astroos['profile_name_en'] = $astr->profile_name_en;
                $astroos['profile_name_hn'] = $astr->profile_name_hn;
                $astroos['gender'] = $astr->gender;
                $astroos['birth_place'] = $astr->birth_place;
                $astroos['dob'] = $astr->dob;
                $astroos['rating'] = $astr->rating;
                $astroos['slug'] = $astr->slug;
                $astroos['about_me_hn'] = $astr->about_me_hn;
                $astroos['about_me_en'] = $astr->about_me_en;
                $astroos['specialisation'] = $astr->specialisation;
                $astroos['all_skills'] = $astr->all_skills;
                $astroos['languages'] = $astr->languages;
                $astroos['experience'] = $astr->experience;
                $astroos['country'] = $astr->country;
                $astroos['state'] = $astr->state;
                $astroos['city'] = $astr->city;
                $astroos['address'] = $astr->address;
                $astroos['zip_code'] = $astr->zip_code;
                $astroos['is_login'] = $astr->is_login;
                $astroos['availability'] = $astr->availability;
                $astroos['astro_call_charges'] = $astr->astro_call_charges;
                $astroos['astro_chat_charges'] = $astr->astro_chat_charges;
                $astroos['call_commission'] = $astr->call_commission;
                $astroos['chat_commission'] = $astr->chat_commission;
                $astroos['disc_call_charge'] = $astr->disc_call_charge;
                $astroos['disc_chat_charge'] = $astr->disc_chat_charge;
                $astroos['astro_video_charges'] = $astr->astro_video_charges;
                $astroos['video_commission'] = $astr->video_commission;
                $astroos['disc_video_charge'] = $astr->disc_video_charge;
                $astroos['gift_commission'] = $astr->gift_commission;
                $astroos['flags'] = $astr->flags;
                $astroos['top_10s'] = $astr->top_10s;
                $astroos['device_id'] = $astr->device_id;
                $astroos['image_path'] = $astr->image_path;
                $astroos['user_token'] = $astr->user_token;
                $astroos['token_expiry'] = $astr->token_expiry;
                $astroos['user_otp'] = $astr->user_otp;
                $astroos['otp_expire'] = $astr->otp_expire;
                $astroos['balance_amount'] = $astr->balance_amount;
                $astroos['promo_balance'] = $astr->promo_balance;
                $astroos['last_rechages'] = $astr->last_rechages;
                $astroos['last_recharge_at'] = $astr->last_recharge_at;
                $astroos['aadhar_doc'] = $astr->aadhar_doc;
                $astroos['profile_image'] = $astr->profile_image;
                $astroos['is_promotional_assign'] = $astr->is_promotional_assign;
                $astroos['is_promotional_accept'] = $astr->is_promotional_accept;
                $astroos['label'] = $astr->label;
                $astroos['celebrity_astro'] = $astr->celebrity_astro;
                $astroos['address_details'] = $astr->address_details;
                $astroos['is_updated'] = $astr->is_updated;
                $astroos['skills'] = implode(',', explodespecialization($astr->specialisation, 'en'));
                $astroos['language_name'] = explodesLanguage($astr->languages);

                $astroos['image'] = image_url($astr->image, '/public/cms-images/user-images/');

                $astro[] = $astroos;
            }
        }
        // $res = array($result,$astro);
        $data = [

            'astrologer' => @$astro ? @$astro : array(),
            'product' => @$result ? @$result : array(),
            //  'test'     =>  @$onlineAstrologer ? @$onlineAstrologer : array(),

        ];
        return  ApiResponse(200, true, 'success', $data);
    }

    public function specialisation(Request $request)
    {
        try {
            $data = DB::table('mst_specialisation')->get();
            return  ApiResponse(200, true, 'success', $data);
        } catch (\Throwable $th) {
            return  InternalError($th->getMessage());
        }
    }

    public function getvideosdkToken(Request $request)
    {
        try {
            $getVideoSdkTOken = getVideoSdkTOken();
            return  ApiResponse(200, true, 'success', $getVideoSdkTOken);
        } catch (\Throwable $th) {
            return  InternalError($th->getMessage());
        }
    }

    public function getSdkRoomId(Request $request)
    {
        try {
            $getVideoSdkTOken = getSDKRoomid();
            return  ApiResponse(200, true, 'success', $getVideoSdkTOken);
        } catch (\Throwable $th) {
            return  InternalError($th->getMessage());
        }
    }
}
