<?php

use App\Models\Admin\Project;
use App\Models\CallChatRequest;
use App\Models\PostProperty\Property;
use App\Models\User\UsersDetail;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use App\Models\MobileDevices;
use App\helpers\ImageHelper;

function generateOTP()
{
    $otpNo = rand(1234, 9999);
    return $otpNo;
}

function sendOtp($otpNo, $countryCode, $mobileNo)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://restapi.smscountry.com/v0.1/Accounts/xNGbUI4eeGAPnOKg6Jfx/SMSes/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
      "Text": "' . $otpNo . ' is your one time password (OTP) for login by AstroERA",
      "Number": "' . $countryCode . '' . $mobileNo . '",
      "SenderId": "ATRERA",
      "DRNotifyUrl": "https://www.astroera.in/",
      "DRNotifyHttpMethod": "POST",
      "Tool": "API"
      
    }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic eE5HYlVJNGVlR0FQbk9LZzZKZng6UE5XY2JBNmV6cHcxc010ZUlJNUk4M050ZmUzN1VsWjdsUUtucEo4MQ=='
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $data_show = json_decode($response);
    return $data_show;
}

function explodespecialization($specialisationIds, $lang = '')
{
    $fieldtype = 'specialisation';
    if (!empty($lang) && $lang == 'hi') {
        $fieldtype = 'specialisation_hindi';
    }

    $specialisationIds = explode(',', $specialisationIds);
    return DB::table('mst_specialisation')
        ->whereIn('id', $specialisationIds)
        ->pluck("$fieldtype")
        ->toArray();
}

function getLabelName($lableid)
{

    if (!empty($lableid)) {

        return DB::table('astro_labels')
            ->where('id', $lableid)
            ->pluck('title')->first();
    }
}


function explodesLanguage($explodeId)
{
    $explodeId = explode(',', $explodeId);
    return DB::table('mst_languages')
        ->whereIn('id', $explodeId)->pluck('language_name')->implode(',');
}


function explodesTag($tagIds)
{
    if (!empty($tagIds)) {


        $tagIds = explode(',', $tagIds);
        return DB::table('tags')
            ->whereIn('id', $tagIds)
            ->pluck('name')
            ->toArray();
    }
}



function getLocationByLatLong($lat, $long)
{
    $apiKey = env('US1_LOCATION_KEY');
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://us1.locationiq.com/v1/reverse.php?key=$apiKey&lat=$lat&lon=$long&format=json",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verification for testing (consider enabling for production)
    ]);

    $response = curl_exec($curl);
    $err      = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return $err;
    } else {
        $jsondata = json_decode($response);
        $city = 706; // delhi
        $state = 10; // delhi
        $country = 101;


        $responsedata = [
            'postcode' => $jsondata->address->postcode,
            'city' => [
                'id' => $city,
                'name' => 'delhi'
            ],
            'state' => [
                'id' => $state,
                'name' => 'delhi'
            ],
            'country' => [
                'id' => $country,
                'name' => 'India'
            ]
        ];
        if (!empty($jsondata->address->city)) {
            $city = DB::table('mst_cities')->where('city_name', $jsondata->address->city)->value('id')
                ?? DB::table('mst_cities')->where('city_name', 'LIKE', '%' . $jsondata->address->city . '%')->pluck('id')->first();
            $responsedata['city'] = [
                'id' => $city,
                'name' => $jsondata->address->city,
            ];
        }
        if (!empty($jsondata->address->state)) {


            $state = DB::table('mst_states')->where('state_name', $jsondata->address->state)->value('id')
                ?? DB::table('mst_states')->where('state_name', 'LIKE', '%' . $jsondata->address->state . '%')->pluck('id')->first();
            $responsedata['state'] = [
                'id' => $state,
                'name' => $jsondata->address->state,
            ];
        }
        if (!empty($jsondata->address->country)) {

            $country = DB::table('mst_countries')->where('country_name', $jsondata->address->country)->value('id')
                ?? DB::table('mst_countries')->where('country_name', 'LIKE', '%' . $jsondata->address->country . '%')->pluck('id')->first();
            $responsedata['country'] = [
                'id' => $country,
                'name' => $jsondata->address->country,
            ];
        }
        return $responsedata;
    }
}





function  guzzleRequestPost($url, $body)
{
    try {
        $token = 'Bearer ' . env('DIVINE_TOKEN');
        $response = Http::withToken($token)->post($url, $body);

        return json_decode($response->getBody()->getContents(), true);
    } catch (\Throwable $th) {
        return $th->getMessage();
    }
}

function InternalError($error)
{
    return response()->json([
        'statusCode' => 500,
        'status' => false,
        'message' => 'something went wrong',
        'errors' => $error
    ]);
}

function SimpleResponse($stausCode, $status, $message)
{
    return response()->json([
        'statusCode' => $stausCode,
        'status' => $status,
        'message' => $message,
    ]);
}

function ApiResponse($statusCode, $status, $message, $data = '')
{
    return response()->json([
        'statusCode' => $statusCode,
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
}

function errorResponse($error, $message = 'Please Fill Mandtatory Fields')
{
    return response()->json([
        'statusCode' => 403,
        'status' => false,
        'message' => $message,
        'error' => $error
    ]);
}

function  genererateErrorFields($fieldsname, $message)
{
    $errors = [
        $fieldsname => [$message]
    ];
    return $errors;
}

function generateConsultId($pretext)
{
    $num = rand(10, 9999999999);
    $session_id = $pretext . sprintf("%07d", $num);
    return $session_id;
}

function getStateName($stateid)
{
    $state = DB::table('mst_states')->where('id', $stateid)->pluck('state_name')->first();
    return $state;
}

function getCountyName($countryid)
{
    $name = DB::table('mst_countries')->where('id', $countryid)->pluck('country_name')->first();
    return $name;
}


function getTotalDuration($obj)
{
    $user_start_time = $obj->astro_start_time;
    $user_end_time = $obj->astro_end_time;
    $astro_start_time = $obj->astro_start_time;
    $astro_end_time = $obj->astro_end_time;
    $start_time = 0;
    $end_time = 0;
    if (strtotime($user_start_time) > strtotime($astro_start_time)) {
        $start_time = $user_start_time;
    } else {
        $start_time = $astro_start_time;
    }
    if ($astro_start_time != '' && $user_start_time != '') {
        if ($user_end_time != '') {
            if ($astro_end_time != '') {
                if (strtotime($user_end_time) < strtotime($astro_end_time)) {
                    $end_time = $user_end_time;
                } else {
                    $end_time = $astro_end_time;
                }
            } else {
                $end_time = $user_end_time;
            }
        } else {
            $end_time = $astro_end_time;
        }
    }

    if ($end_time != 0) {
        $totDuration = strtotime($end_time) - strtotime($start_time);
        //  $totDuration = $totDuration > 5 ? $totDuration - 2 : $totDuration;
    } else {
        $totDuration = 0;
    }
    return $totDuration;
}

function UpdateUserLedger($usertype, $user_id, $session_id, $cbalance_amount, $upatedbalance, $transaction_balance, $condtion, $is_promotional, $chatstatus, $transaction_type)
{
    DB::table('user_transaction_ledeger')->insert([
        'usertype' => 'user',
        'user_id' => $user_id,
        'transaction_id' => $session_id,
        'created_at' => date('Y-m-d H:i:s', time()),
        'current_balance' => $cbalance_amount,
        'new_balance' => $upatedbalance,
        'top_up_balance' => $transaction_balance,
        'conditions' => $condtion,
        'is_promotional' => $is_promotional,
        'chat_status' => $chatstatus,
        'transaction_type' => $transaction_type,
    ]);
}


function getWaitingTime($user_id)
{

    $ast_id = $user_id;
    $all_time = 0;
    $time_duration = array();
    $alluser = array();
    $current_status = CallChatRequest::Where('expert_id', $ast_id)->Where('request_status', 2)->first();

    if ($current_status != "") {

        $balance_user = UsersDetail::where('user_id', $current_status->user_id)->first();
        $astro_start_time = $current_status->astro_start_time;
        $start_time = strtotime($astro_start_time);
        $now_time = strtotime(date("Y-m-d H:i:s"));
        if ($current_status->request_type == 'Chat') {
            if ($current_status->is_promotional == 1) {
                $astrologer_time = 180;
            } else {
                $astrocallCharges = $current_status->astro_chat_charge;
                $astrologer_time = floor($balance_user->balance_amount / $astrocallCharges) * 60;
            }
        } else if ($current_status->request_type == 'Calling') {
            $astrocallCharges = $current_status->astro_call_chagre;
            $astrologer_time = floor($balance_user->balance_amount / $astrocallCharges) * 60;
        } else if ($current_status->request_type == 'Video') {
            $astrocallCharges = $current_status->astro_video_call_charge;
            $astrologer_time = floor($balance_user->balance_amount / $astrocallCharges) * 60;
        } else if ($current_status->request_type == 'Webinar') {
            $astrologer_time = 0;
        }
        $total_duration = $astrologer_time;
        $start_end_time_diff = $start_time + $total_duration;
        $total_time = $start_end_time_diff - $now_time;
        $my_maxClientCallDuration = $total_time / 60;

        $time_duration[] = $total_time;
    }

    $astro_status = CallChatRequest::Where('expert_id', $ast_id)->Where('request_status', 20)->get();

    if ($astro_status != "") {

        foreach ($astro_status as $astro) {


            $user = UsersDetail::where('user_id', $astro->user_id)->first();

            $user_balance = $user->balance_amount;
            $time = 0;
            if ($astro->request_type == "Chat") {
                if ($astro->is_promotional == 1) {
                    $time = 3;
                } else {
                    $astro_charge = $astro->astro_chat_charge;
                }
            } else if ($astro->request_type == "Calling") {
                $astro_charge = $astro->astro_call_chagre;
            } else if ($astro->request_type == "Video") {
                $astro_charge = $astro->astro_video_call_charge;
            }
            if (!empty($astro_charge)) {
                $time = $user_balance / $astro_charge;
            }
            $time_duration[] = $time;
            $alluser[] = $astro->user_id;
        }
    }

    $approx_time = array_sum($time_duration);

    $all_time = floor($approx_time);

    if ($all_time == 0) {

        return $apprx = "0";
    } else {

        return $apprx = "$all_time";
    }
}




function getWaitingTimeShortdata($user_id)
{

    $ast_id = $user_id;
    $all_time = 0;
    $time_duration = array();
    $alluser = array();
    $current_status = CallChatRequest::Where('expert_id', $ast_id)->Where('request_status', 2)->first();

    if ($current_status != "") {

        $balance_user = UsersDetail::where('user_id', $current_status->user_id)->first();
        $astro_start_time = $current_status->astro_start_time;
        $start_time = strtotime($astro_start_time);
        $now_time = strtotime(date("Y-m-d H:i:s"));
        if ($current_status->request_type == 'Chat') {
            if ($current_status->is_promotional == 1) {
                $astrologer_time = 180;
            } else {
                $astrocallCharges = $current_status->astro_chat_charge;
                $astrologer_time = floor($balance_user->balance_amount / $astrocallCharges) * 60;
            }
        } else if ($current_status->request_type == 'Calling') {
            $astrocallCharges = $current_status->astro_call_chagre;
            $astrologer_time = floor($balance_user->balance_amount / $astrocallCharges) * 60;
        } else if ($current_status->request_type == 'Video') {
            $astrocallCharges = $current_status->astro_video_call_charge;
            $astrologer_time = floor($balance_user->balance_amount / $astrocallCharges) * 60;
        } else if ($current_status->request_type == 'Webinar') {
            $astrologer_time = 0;
        }
        $total_duration = $astrologer_time;
        $start_end_time_diff = $start_time + $total_duration;
        $total_time = $start_end_time_diff - $now_time;
        $my_maxClientCallDuration = $total_time / 60;

        $time_duration[] = $total_time;
    }

    $astro_status = CallChatRequest::Where('expert_id', $ast_id)->Where('request_status', 20)->get();

    if ($astro_status != "") {

        foreach ($astro_status as $astro) {


            $user = UsersDetail::where('user_id', $astro->user_id)->first();

            $user_balance = $user->balance_amount;
            $time = 0;
            if ($astro->request_type == "Chat") {
                if ($astro->is_promotional == 1) {
                    $time = 3;
                } else {
                    $astro_charge = $astro->astro_chat_charge;
                }
            } else if ($astro->request_type == "Calling") {
                $astro_charge = $astro->astro_call_chagre;
            } else if ($astro->request_type == "Video") {
                $astro_charge = $astro->astro_video_call_charge;
            }
            if (!empty($astro_charge)) {
                $time = $user_balance / $astro_charge;
            }
            $time_duration[] = $time;
            $alluser[] = $astro->user_id;
        }
    }

    $approx_time = array_sum($time_duration);

    $all_time = floor($approx_time);
    if ($all_time >= 1620) {
        $all_time = 1620;
    }

    if ($all_time == 0) {

        return $apprx = "0";
    } else {

        return $apprx = "$all_time";
    }
}

function getWaitingTimeshortSingle($expertId, $currentUserId)
{
    $activeRequests = CallChatRequest::where('expert_id', $expertId)
        ->where('request_status', 2)
        ->orderBy('id')
        ->get();

    $queuedRequests = CallChatRequest::where('expert_id', $expertId)
        ->where('request_status', 20)
        ->orderBy('id')
        ->get();

    $allRequests = $activeRequests->merge($queuedRequests);

    if ($allRequests->isEmpty()) {
        return 0;
    }

    $userIds = $allRequests->pluck('user_id')->unique();
    $userBalances = UsersDetail::whereIn('user_id', $userIds)
        ->pluck('balance_amount', 'user_id')
        ->toArray();

    $cumulativeWait = 0;

    foreach ($allRequests as $req) {
        $type = strtolower($req->request_type);
        $balance = $userBalances[$req->user_id] ?? 0;
        $duration = 0;

        if ($req->request_status == 2) {
            if ($type === 'chat') {
                $duration = $req->is_promotional ? 180 : ($req->astro_chat_charge > 0 ? round($balance / $req->astro_chat_charge) * 60 : 0);
                $start = strtotime($req->astro_start_time);
            } elseif ($type === 'calling') {
                $duration = $req->astro_call_chagre > 0 ? round($balance / $req->astro_call_chagre) * 60 : 0;
                $start = time();

                $exotel = DB::table('exotels')->where('session_id', $req->request_session_id)->orderByDesc('id')->first();
                if ($exotel && !empty($exotel->data)) {
                    $entriesRaw = json_decode($exotel->data, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($entriesRaw)) {
                        $data = json_decode($entriesRaw[0] ?? '', true);
                        if (isset($data['EventTime'])) {
                            $start = strtotime($data['EventTime']);
                        }
                    }
                }
            } elseif ($type === 'video') {
                $duration = $req->astro_video_call_charge > 0 ? round($balance / $req->astro_video_call_charge) * 60 : 0;
                $start = strtotime($req->astro_start_time);
            }

            $remaining = max(($start + $duration) - time(), 0);

            if ($req->user_id == $currentUserId) {
                return $remaining;
            }

            $cumulativeWait = $remaining;
        }

        if ($req->request_status == 20) {
            if ($type === 'chat') {
                $duration = $req->is_promotional ? 180 : ($req->astro_chat_charge > 0 ? round($balance / $req->astro_chat_charge) * 60 : 0);
            } elseif ($type === 'calling') {
                $duration = $req->astro_call_chagre > 0 ? round($balance / $req->astro_call_chagre) * 60 : 0;
            } elseif ($type === 'video') {
                $duration = $req->astro_video_call_charge > 0 ? round($balance / $req->astro_video_call_charge) * 60 : 0;
            }

            if ($req->user_id == $currentUserId) {
                return $cumulativeWait;
            }

            $cumulativeWait += $duration;
        }
    }

    return 0; // Default
}



function getWaitingTimeshortBulk(array $userIds)
{
    $waitTimes = [];

    $requests = CallChatRequest::whereIn('expert_id', $userIds)
        ->whereIn('request_status', [2, 20])
        ->get()
        ->groupBy('expert_id');

    $userIdsForBalance = $requests->flatMap(function ($reqs) {
        return $reqs->pluck('user_id');
    })->unique()->toArray();

    $userBalances = UsersDetail::whereIn('user_id', $userIdsForBalance)
        ->pluck('balance_amount', 'user_id')
        ->toArray();

    foreach ($userIds as $ast_id) {
        $time_duration = [];
        $reqs = $requests->get($ast_id, collect());

        foreach ($reqs as $current_status) {
            $userId = $current_status->user_id;
            $balance = $userBalances[$userId] ?? 0;
            $type = strtolower($current_status->request_type);
            $wait_time = 0;

            // Status 2: Active
            if ($current_status->request_status == 2) {
                $start_time = strtotime($current_status->astro_start_time);
                $now_time = time();
                $astro_time = 0;

                if ($type === 'chat') {
                    $astro_time = $current_status->is_promotional == 1
                        ? 180
                        : ($current_status->astro_chat_charge > 0
                            ? floor($balance / $current_status->astro_chat_charge) * 60
                            : 0);
                } elseif ($type === 'calling') {
                    // new code for call start  
                    $exotelRecord = DB::table('exotels')
                        ->where('session_id', $current_status->request_session_id)
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($exotelRecord && !empty($exotelRecord->data)) {
                        $raw = $exotelRecord->data;

                        $entriesRaw = json_decode($raw, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {

                            // Try to fix if invalid JSON by removing outer brackets and decoding manually
                            if (preg_match('/^\["(.*)"\]$/s', $raw, $matches)) {
                                $jsonString = $matches[1];
                                $jsonString = stripslashes($jsonString); // remove escaped quotes

                                $entries = json_decode($jsonString, true);
                            }
                        } elseif (is_array($entriesRaw)) {
                            $jsonString = $entriesRaw[0] ?? null;
                            $entries = json_decode($jsonString, true);
                        }

                        if (isset($entries) && json_last_error() === JSON_ERROR_NONE && is_array($entries)) {

                            if (
                                isset($entries['EventType'], $entries['Status'], $entries['EventTime']) &&
                                $entries['EventType'] === 'answered' &&
                                $entries['Status'] === 'in-progress'
                            ) {
                                $start_time = strtotime($entries['EventTime']);
                            }
                        }
                    }
                    // new code for call end

                    $astro_time = $current_status->astro_call_chagre > 0
                        ? floor($balance / $current_status->astro_call_chagre) * 60
                        : 0;
                } elseif ($type === 'video') {
                    $astro_time = $current_status->astro_video_call_charge > 0
                        ? floor($balance / $current_status->astro_video_call_charge) * 60
                        : 0;
                }

                $wait_time = max(($start_time + $astro_time) - $now_time, 0);
            }

            // Status 20: In Queue
            if ($current_status->request_status == 20) {
                if ($type === 'chat') {
                    $wait_time = $current_status->is_promotional == 1
                        ? 180
                        : ($current_status->astro_chat_charge > 0
                            ? floor($balance / $current_status->astro_chat_charge) * 60
                            : 0);
                } elseif ($type === 'calling' && $current_status->astro_call_chagre > 0) {
                    $wait_time = floor($balance / $current_status->astro_call_chagre) * 60;
                } elseif ($type === 'video' && $current_status->astro_video_call_charge > 0) {
                    $wait_time = floor($balance / $current_status->astro_video_call_charge) * 60;
                }
            }

            $time_duration[] = $wait_time; // all in seconds
        }

        $totalSeconds = array_sum($time_duration);
        $waitTimes[$ast_id] = min($totalSeconds, 1620); // cap at 1620 min = 97200 sec
    }

    return $waitTimes;
}




function getmaxExpectedTime($sessionid)
{

    $items = CallChatRequest::leftjoin('users as expert', 'expert.id', '=', 'call_chat_request.expert_id')
        ->leftjoin('users as client', 'client.id', '=', 'call_chat_request.user_id')
        ->leftjoin('users_details as clientDetails', 'clientDetails.user_id', '=', 'call_chat_request.user_id')
        ->leftjoin('users_details as expertDetails', 'expertDetails.user_id', '=', 'call_chat_request.expert_id')
        ->select('call_chat_request.*', 'expert.name as expert_name', 'expert.mobile as expert_mobile', 'expert.image as expert_image', 'client.name as client_name', 'client.mobile as client_mobile', 'client.image as client_image', 'clientDetails.balance_amount', 'expertDetails.astro_call_charges', 'expertDetails.disc_call_charge')
        ->where('call_chat_request.request_session_id', $sessionid)
        ->where(function ($query) {
            $query->where('call_chat_request.request_status', 1)->orwhere('call_chat_request.request_status', 2)->orwhere('call_chat_request.request_status', 20);
        })
        ->first();
    if ($items) {
        if ($items->is_promotional == 1 && $items->request_type == 'Chat') {
            $astrocallCharges = $items->astro_chat_charge;
            $isCallStart = true;
            $maxClientCallDuration = 180;
        } else {
            $astrocallCharges = $items->astro_chat_charge;
            if ($items->request_type == 'Video' || $items->request_type == 'video') {
                $astrocallCharges = $items->astro_video_call_charge;
            } elseif ($items->request_type == 'Calling') {
                $astrocallCharges = $items->astro_call_chagre;
            }
            if ($items->balance_amount > 0) {
                if ($items->balance_amount > $astrocallCharges) {
                    $maxClientCallDuration = floor($items->balance_amount / $astrocallCharges) * 60;
                } else {
                    $maxClientCallDuration = 0;
                }
            } else {
                $maxClientCallDuration = 0;
            }
        }
        $start_time = strtotime($items->astro_start_time);
        $now_time = strtotime(date("Y-m-d H:i:s"));
        $total_duration = $maxClientCallDuration;
        $start_end_time_diff = $start_time + $total_duration;

        // $total_time=$start_end_time_diff-$now_time;
        // $my_maxClientCallDuration=$total_time;  


        return [
            'status' => true,
            'message' => "success",
            "data" => [
                'max_time' => date('Y-m-d H:i:s', $start_end_time_diff),
                'duration' => $total_duration
            ]
        ];
    }
    return [
        'status' => false,
        'message' => 'session not active'
    ];
}





function encryptGateway($plainText, $key)
{
    $key = hextobin(md5($key));
    $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
    $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
    $encryptedText = bin2hex($openMode);
    return $encryptedText;
}





function decryptGateway($encryptedText, $key)
{
    $key = hextobin(md5($key));
    $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
    $encryptedText = hextobin($encryptedText);
    $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
    return $decryptedText;
}


function hextobin($hexString)
{
    $length = strlen($hexString);
    $binString = "";
    $count = 0;
    while ($count < $length) {
        $subString = substr($hexString, $count, 2);
        $packedString = pack("H*", $subString);
        if ($count == 0) {
            $binString = $packedString;
        } else {
            $binString .= $packedString;
        }

        $count += 2;
    }
    return $binString;
}


function getFcmToken($userid)
{
    $data = MobileDevices::where('user_id', $userid)->first();
    if ($data) {
        return $data;
    } else {
        return false;
    }
}




function getWaitingTimeshort($user_id)
{

    $ast_id = $user_id;
    $all_time = 0;
    $time_duration = array();
    $alluser = array();
    $current_status = CallChatRequest::Where('expert_id', $ast_id)->Where('request_status', 2)->first();

    if ($current_status != "") {

        $balance_user = UsersDetail::where('user_id', $current_status->user_id)->first();
        $astro_start_time = $current_status->astro_start_time;
        $start_time = strtotime($astro_start_time);
        $now_time = strtotime(date("Y-m-d H:i:s"));
        if ($current_status->request_type == 'Chat') {
            if ($current_status->is_promotional == 1) {
                $astrologer_time = 180;
            } else {
                $astrocallCharges = $current_status->astro_chat_charge;
                if ($astrocallCharges > 0) {
                    $astrologer_time = floor($balance_user->balance_amount / $astrocallCharges) * 60;
                } else {
                    $astrologer_time = 0;
                }
            }
        } else if ($current_status->request_type == 'Calling') {
            $astrocallCharges = $current_status->astro_call_chagre;
            if ($astrocallCharges > 0) {
                $astrologer_time = floor($balance_user->balance_amount / $astrocallCharges) * 60;
            } else {
                $astrologer_time = 0;
            }
        } else if ($current_status->request_type == 'Video' || $current_status->request_type == 'video') {
            $astrocallCharges = $current_status->astro_video_call_charge;
            if ($astrocallCharges > 0) {
                $astrologer_time = floor($balance_user->balance_amount / $astrocallCharges) * 60;
            } else {
                $astrologer_time = 0;
            }
        } else if ($current_status->request_type == 'Webinar') {
            $astrologer_time = 0;
        } else {
            $astrologer_time = 0;
        }
        $total_duration = $astrologer_time;
        $start_end_time_diff = $start_time + $total_duration;
        $total_time = $start_end_time_diff - $now_time;
        $my_maxClientCallDuration = $total_time / 60;

        $time_duration[] = $total_time;
    }

    $astro_status = CallChatRequest::Where('expert_id', $ast_id)->Where('request_status', 20)->get();

    if ($astro_status != "") {

        foreach ($astro_status as $astro) {


            $user = UsersDetail::where('user_id', $astro->user_id)->first();

            $user_balance = $user->balance_amount;
            $time = 0;
            if ($astro->request_type == "Chat") {
                if ($astro->is_promotional == 1) {
                    $time = 3;
                } else {
                    $astro_charge = $astro->astro_chat_charge;
                }
            } else if ($astro->request_type == "Calling") {
                $astro_charge = $astro->astro_call_chagre;
            } else if ($astro->request_type == "Video") {
                $astro_charge = $astro->astro_video_call_charge;
            }
            if (!empty($astro_charge)) {
                $time = $user_balance / $astro_charge;
            }
            $time_duration[] = $time;
            $alluser[] = $astro->user_id;
        }
    }

    $approx_time = array_sum($time_duration);

    $all_time = floor($approx_time);
    if ($all_time >= 1620) {
        $all_time = 1620;
    }

    if ($all_time == 0) {

        return $apprx = "0";
    } else {

        return $apprx = "$all_time";
    }
}


function base64UrlEncodemycode($text)
{
    return str_replace(
        ['+', '/', '='],
        ['-', '_', ''],
        base64_encode($text)
    );
}


function getVideoSdkTOken()
{
    $VIDEOSDK_API_KEY = "96ad837d-4fe8-423d-a3e5-18846f909979";
    $VIDEOSDK_SECRET_KEY = "f3cb37e928ab6b2d342434aa0c40fa3cefdcc3b1d6ca8e4812cf1bb4342cb1f5";
    $issuedAt = new DateTimeImmutable();
    $expire = $issuedAt->modify('+2 hours')->getTimestamp();
    $payload = [
        'apikey' => $VIDEOSDK_API_KEY,
        'permissions' => [
            "allow_join"
        ],
        'version' => 2,
        'roles' => [
            'crawler'
        ],
        'iat' => $issuedAt->getTimestamp(),
        'exp' => $expire
    ];

    $jwt = Firebase\JWT\JWT::encode($payload, $VIDEOSDK_SECRET_KEY, 'HS256');
    return $jwt;
}

function generateCustomRoomId()
{
    $part1 = substr(md5(uniqid()), 0, 3);
    $part2 = substr(md5(uniqid()), 0, 3);
    $part3 = substr(md5(uniqid()), 0, 3);
    return $part1 . '-' . $part2 . '-' . $part3;
}

function getSDKRoomid()
{
    // not working with
    // 'roles' => [
    //     'crawler',
    //     'rtc'
    // ],


    $VIDEOSDK_API_KEY = "d4c1cb71-2fb1-4227-807f-74279b8424fe";
    $VIDEOSDK_SECRET_KEY = "5ae440ba4ab1c632405a6c25b753339ecbda3d394cb4e1b6dd6909dc9b544a1a";
    $issuedAt = new DateTimeImmutable();
    $expire = $issuedAt->modify('+2 hours')->getTimestamp();
    $payload = [
        'apikey' => $VIDEOSDK_API_KEY,
        'permissions' => [
            "allow_join"
        ],
        'version' => 2,
        'iat' => $issuedAt->getTimestamp(),
        'exp' => $expire
    ];

    $jwt = Firebase\JWT\JWT::encode($payload, $VIDEOSDK_SECRET_KEY, 'HS256');




    $curl = curl_init();
    $data = array(
        "customRoomId" => generateCustomRoomId()
    );
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.videosdk.live/v2/rooms",
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $jwt,
            'Content-Type: application/json'
        ),
        CURLOPT_POSTFIELDS => json_encode($data),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($response);

    if (!empty($result)) {
        $data = [
            'token' => $jwt,
            'meet_id' => $result->roomId,
        ];
        return $data;
    }
}

function image_url($path = '', $path2 = '', $filename = '')
{
    return ImageHelper::getImageUrl($path, $path2, $filename);
}
