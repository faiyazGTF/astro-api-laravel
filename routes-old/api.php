<?php
use App\Http\Controllers\Controller;
use App\Http\Controllers\Common\CommonController;
use App\Http\Controllers\CommonController as DefaultCommonController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\NoticeBoardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FireBaseActionController;
use App\Models\User\User;

use App\Http\Controllers\ChatAndCallController;


$userRoutes = glob(__DIR__ . "/UserRoutes/*.php");
$Poojas = glob(__DIR__ . "/Pooja/*.php");


$astrologer = glob(__DIR__ . "/astrologer/*.php");


foreach ($userRoutes as $route) require($route);
foreach ($Poojas as $route) require($route);


foreach ($astrologer as $route) require($route);

Route::get('/search-location', [CommonController::class, 'searchlocation']);

Route::post('/save-socket-token', [ChatAndCallController::class, 'saveSocketToken']);
Route::post('/get-socket-token', [ChatAndCallController::class, 'getSocketToken']);

// for testing s3 
Route::get('/upload-test', [Controller::class, 'uploadToS3']);
Route::get('/check-s3-files', [Controller::class, 'checkS3Files']);
// end

Route::group(['prefix' => 'admin'], function () {
    Route::get('faq', [FAQController::class, 'index']);
    Route::post('faq', [FAQController::class, 'store']);
    Route::get('faq/{id}', [FAQController::class, 'show']);
    Route::put('faq/{id}', [FAQController::class, 'update']);
    Route::delete('faq/{id}', [FAQController::class, 'destroy']);  




    Route::get('notice-board', [NoticeBoardController::class, 'index']);
    Route::post('notice-board', [NoticeBoardController::class, 'store']);
    Route::get('notice-board/{id}', [NoticeBoardController::class, 'show']);
    Route::put('notice-board/{id}', [NoticeBoardController::class, 'update']);
    Route::delete('notice-board/{id}', [NoticeBoardController::class, 'destroy']);  

});
Route::get('languages', [DefaultCommonController::class, 'languages']);  

Route::post('upload-file', [DefaultCommonController::class, 'uploadfile']);  
Route::post('end-session', [DefaultCommonController::class, 'endsession']);  
Route::get('get-conferencing-broadcasting', [DefaultCommonController::class, 'conferencingAstro']);  
Route::post('global-search',[DefaultCommonController::class, 'global_search']);
Route::get('specialisation', [DefaultCommonController::class, 'specialisation']);  
Route::get('get-videosdk-token', [DefaultCommonController::class, 'getvideosdkToken']);  

Route::get('get-video-roomId', [DefaultCommonController::class, 'getSdkRoomId']); 
Route::post('notification', function(Request $request){
    $token=$request->token;
    $title=$request->title;
    $message=$request->message;
	
    FireBaseActionController::PushNOtification($token,$title,$message);
});  


Route::post('user-notification', function(Request $request){
    $token=[
		'token'=>$request->token,
		'device_platform'=>$request->token
	];
 
	$myarray=[
	'title'=>$request->title,
	'message'=>$request->message,
	'type'=>$request->type,
	'senderid'=>$request->senderid
	];

	
    FireBaseActionController::PushNOtificationAuthdata($token,$myarray);
});  

//start extra code
Route::get('fetch-file-audio', function (Request $request) {
	
	$accessToken = 'a499bb19783209aed6760ce86e50bcfb227a70623abee826';
	$audioUrl = 'https://recordings.exotel.com/exotelrecordings/svngstripandwire2/fa1fc77f812fb892d71a9c072df4193r.mp3';

	// Prepare headers for the cURL request
	$headers = [
		"Authorization: Bearer $accessToken"
	];

	// If the client request includes a Range header, forward it
	if (isset($_SERVER['HTTP_RANGE'])) {
		$headers[] = "Range: " . $_SERVER['HTTP_RANGE'];
	}

	// Initialize cURL and set options
	$ch = curl_init($audioUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HEADER, true); // include headers in output
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	// Execute the cURL request
	$response = curl_exec($ch);
	if (curl_errno($ch)) {
		http_response_code(500);
		echo 'Error fetching audio: ' . curl_error($ch);
		curl_close($ch);
		exit;
	}

	// Retrieve header size and HTTP response code
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	// Separate headers from body
	$responseHeaders = substr($response, 0, $headerSize);
	$body = substr($response, $headerSize);

	// Send the HTTP response code from the partner API (e.g., 206 for partial content)
	http_response_code($httpCode);

	// Parse the remote headers and forward relevant ones to the client
	$headerLines = explode("\r\n", $responseHeaders);
	foreach ($headerLines as $header) {
		if (
			stripos($header, 'Content-Type:') !== false ||
			stripos($header, 'Content-Length:') !== false ||
			stripos($header, 'Content-Range:') !== false ||
			stripos($header, 'Accept-Ranges:') !== false
		) {
			header($header);
		}
	}

	// Output the audio content directly
	return $body;

});

//extra code 


Route::post('mtstatus', function (Request $request) {
    
    $data = json_encode($request->all());
   
    
  


    $dataArray = json_decode($data, true);

// The first element of the array contains the actual JSON, so decode it again
$callData = json_decode($dataArray[0], true);

// Get the CustomField value
$session_id = $callData['CustomField'];
$status = $callData['Status'];
$exotel_callid = $callData['CallSid'];
$ongoingstatus = $callData['Legs'][1]['Status'];
$EndTime=$callData['EndTime'];
$StartTime=$callData['StartTime'];
$EventTime=$callData['EventTime'];
//$StartTime=date('Y-m-d H:i:s',time());
$ConversationDuration = $callData['ConversationDuration'];

if (!empty($ConversationDuration)) {
    $date = new DateTime($StartTime);
    $date->modify('+' . $ConversationDuration . ' seconds');
    $EndTime = $date->format('Y-m-d H:i:s'); // <-- this formats it correctly
		DB::select("INSERT INTO `log_data` (`value`) VALUES ('$EndTime')");
}


 DB::select("INSERT INTO `exotels` (`data`,`session_id`) VALUES ('$data','$session_id')");

        if ($status == 'no-answer') {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://astro-api.iqsetters.in/api/user/chat/end',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'chat_id=' . $session_id . '&user_role=Host&roomEntryType=roomLeave&end_time='.$EndTime,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);
        }

        if ($status == 'failed') {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://astro-api.iqsetters.in/api/user/chat/end',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'chat_id=' . $session_id . '&user_role=Host&roomEntryType=roomLeave&end_time='.$EndTime,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);
        }

        if ($status == 'busy') {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://astro-api.iqsetters.in/api/user/chat/end',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'chat_id=' . $session_id . '&user_role=Host&roomEntryType=roomLeave&end_time='.$EndTime,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);
        }
        if ($ongoingstatus == 'completed') {
            $RecordingUrl = $callData['RecordingUrl'];
            DB::select("UPDATE `call_chat_request` SET `record_url` = '$RecordingUrl',`exotel_callid`='$exotel_callid' WHERE `request_session_id` = '$session_id'");
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://astro-api.iqsetters.in/api/user/chat/end',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'chat_id=' . $session_id . '&user_role=Host&roomEntryType=roomLeave&end_time='.$EndTime,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;
        }
        if ($ongoingstatus == 'in-progress') {
            $curl = curl_init();
		
	
            curl_setopt_array($curl, array(
                
				CURLOPT_URL => 'https://astro-api.iqsetters.in/api/astrologer/accept-session/' . $session_id . '?StartTime=' . urlencode($StartTime) . '&EventTime=' . urlencode($EventTime),

                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',

                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;
        }
});

