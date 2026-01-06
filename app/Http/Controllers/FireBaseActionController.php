<?php

namespace App\Http\Controllers;
use App\Models\CallChatRequest;
use App\Models\MobileDevices;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class FireBaseActionController extends Controller
{
    public static function AstrologerConsultUpdateNew($astro_id,$user_id,$requestname,$userimage,$user_balance,$request_type,$sessionid,$otherdata=[]){

        $status=$otherdata['status'];
        $astro_start_time='';
        $end_by='';

        if(!empty($otherdata['end_by'])){
            $end_by=$otherdata['end_by'];

        }
        $all_users = CallChatRequest::where("expert_id", $astro_id)->where('request_status',20)->count();
        $curl = curl_init();
		
		
		   $expertdata=User::select('users.user_type','users_details.balance_amount','users.id','users.image','users.name')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id',$astro_id)->first();
		$expert_image='';
		$expert_name='';
		$expert_id='';
		if($expertdata){
		
		$expert_image = !empty($expertdata->image) 
    ? image_url($expertdata->image,'/public/cms-images/user-images/')
    : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";

			$expert_name=$expertdata->name;
			$expert_id=$expertdata->id;
			
			}
		
		
		
		
        $url="https://firestore.googleapis.com/v1/projects/astroeranew/databases/(default)/documents/astrologer_consult/$astro_id?updateMask.fieldPaths=user_profile&updateMask.fieldPaths=start_time&updateMask.fieldPaths=total_waitlist_count&updateMask.fieldPaths=user_name&updateMask.fieldPaths=consult_id&updateMask.fieldPaths=user_wallet_balance&updateMask.fieldPaths=user_id&updateMask.fieldPaths=consult_type&updateMask.fieldPaths=consult_status&updateMask.fieldPaths=expert_id&updateMask.fieldPaths=expert_name&updateMask.fieldPaths=expert_image&updateMask.fieldPaths=meet_id&updateMask.fieldPaths=sdk_token&updateMask.fieldPaths=end_by&updateMask.fieldPaths=socket_url";
		$getSDKRoomid='';
		$getSDKtoken='';
		
		if($status == 'active' && ( $request_type=='Video' || $request_type=='video' ) ){
			$tokendata=getSDKRoomid();
				$getSDKRoomid=$tokendata['meet_id'];
				$getSDKtoken=$tokendata['token'];
		}
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'PATCH',
          CURLOPT_POSTFIELDS =>'
                {
                    "name": "projects/astroeranew/databases/(default)/documents/astrologer_consult/'.$astro_id.'",
                    "fields": {
                     "socket_url": {
                        "stringValue": "'.env('SOCKET_SERVER_URL', 'http://localhost:65282').'"
                    }, 
                        "user_profile": {
                            "stringValue": "'.$userimage.'"
                        },
                        "start_time": {
                            "stringValue": "'.$astro_start_time.'"
                        },
                        "total_waitlist_count": {
                            "integerValue": "'.($all_users ? $all_users : 0).'"
                        },
                        "user_name": {
                            "stringValue": "'.$requestname.'"
                        },
                        "consult_id": {
                            "stringValue": "'.$sessionid.'"
                        },
                        "user_wallet_balance": {
                            "integerValue": "'.$user_balance.'"
                        },
                        "user_id": {
                            "stringValue": "'.$user_id.'"
                        },
                        "consult_type": {
                            "stringValue": "'.$request_type.'"
                        },
                        "consult_status": {
                            "stringValue": "'.@$status.'"
                        },
						 "expert_id": {
                            "stringValue": "'.$expert_id.'"
                        },
						 "expert_name": {
                            "stringValue": "'.$expert_name.'"
                        },
						"expert_image": {
                            "stringValue": "'.$expert_image.'"
                        },
						"meet_id":{
						 "stringValue": "'.$getSDKRoomid.'"
						},
						"sdk_token":{
						 "stringValue": "'.$getSDKtoken.'"
						},
                         "end_by":{
						 "stringValue": "'.$end_by.'"
						}
                    },
                    "createTime": "'.date('Y-m-d\TH:i:s.v\Z').'",
                    "updateTime": "'.date('Y-m-d\TH:i:s.v\Z').'"
                   }
                 ',
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                  ),
                ));
                
                $response = curl_exec($curl);
                curl_close($curl);  
    }

    public static function AstrologerConsultUpdate($astro_id,$user_id,$requestname,$userimage,$user_balance,$request_type,$sessionid,$status,$astro_start_time='',$astro_end_time=''){
        $all_users = CallChatRequest::where("expert_id", $astro_id)->where('request_status',20)->count();
        $curl = curl_init();

		   $expertdata=User::select('users.user_type','users_details.balance_amount','users.id','users.image','users.name')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id',$astro_id)->first();
		
		$expert_name='';
		$expert_id='';
		if($expertdata){
		
		$expert_image = !empty($expertdata->image) 
    ? image_url($expertdata->image,'/public/cms-images/user-images/')
    : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";

			$expert_name=$expertdata->name;
			$expert_id=$expertdata->id;
			
			}
		
            $userdata=User::select('image')->where('id',$user_id)->first();
            $userimage=image_url($userdata->image,'/public/cms-images/user-images/');
            




		
		
		
        $url="https://firestore.googleapis.com/v1/projects/astroeranew/databases/(default)/documents/astrologer_consult/$astro_id?updateMask.fieldPaths=user_profile&updateMask.fieldPaths=start_time&updateMask.fieldPaths=end_time&updateMask.fieldPaths=total_waitlist_count&updateMask.fieldPaths=user_name&updateMask.fieldPaths=consult_id&updateMask.fieldPaths=user_wallet_balance&updateMask.fieldPaths=user_id&updateMask.fieldPaths=consult_type&updateMask.fieldPaths=consult_status&updateMask.fieldPaths=expert_id&updateMask.fieldPaths=expert_name&updateMask.fieldPaths=expert_image&updateMask.fieldPaths=meet_id&updateMask.fieldPaths=sdk_token&updateMask.fieldPaths=socket_url";
		$getSDKRoomid='';
		$getSDKtoken='';
		
		if($status == 'active' && ( $request_type=='Video' || $request_type=='video' ) ){
			$tokendata=getSDKRoomid();
				$getSDKRoomid=$tokendata['meet_id'];
				$getSDKtoken=$tokendata['token'];
		}
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'PATCH',
          CURLOPT_POSTFIELDS =>'
                {
                    "name": "projects/astroeranew/databases/(default)/documents/astrologer_consult/'.$astro_id.'",
                    "fields": {
                        "socket_url": {
                            "stringValue": "'.env('SOCKET_SERVER_URL', 'http://localhost:65282').'"
                        },
                        "user_profile": {
                            "stringValue": "'.$userimage.'"
                        },
                        "start_time": {
                            "stringValue": "'.$astro_start_time.'"
                        },
                        "end_time": {
                            "stringValue": "'.$astro_end_time.'"
                        },
                        "total_waitlist_count": {
                            "integerValue": "'.($all_users ? $all_users : 0).'"
                        },
                        "user_name": {
                            "stringValue": "'.$requestname.'"
                        },
                        "consult_id": {
                            "stringValue": "'.$sessionid.'"
                        },
                        "user_wallet_balance": {
                            "integerValue": "'.$user_balance.'"
                        },
                        "user_id": {
                            "stringValue": "'.$user_id.'"
                        },
                        "consult_type": {
                            "stringValue": "'.$request_type.'"
                        },
                        "consult_status": {
                            "stringValue": "'.@$status.'"
                        },
						 "expert_id": {
                            "stringValue": "'.$expert_id.'"
                        },
						 "expert_name": {
                            "stringValue": "'.$expert_name.'"
                        },
						"expert_image": {
                            "stringValue": "'.$expert_image.'"
                        },
						"meet_id":{
						 "stringValue": "'.$getSDKRoomid.'"
						},
						"sdk_token":{
						 "stringValue": "'.$getSDKtoken.'"
						}
                    },
                    "createTime": "'.date('Y-m-d\TH:i:s.v\Z').'",
                    "updateTime": "'.date('Y-m-d\TH:i:s.v\Z').'"
                   }
                 ',
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                  ),
                ));
                
                $response = curl_exec($curl);
                curl_close($curl);  
    }

     
    public static function generateOAuthToken()
    {
    
    
      
    
            // Read service account details
            $authConfigString = file_get_contents(public_path("/firebasenotification.json"));

            // Parse service account details
            $authConfig = json_decode($authConfigString);

            // Read private key from service account details
            $secret = openssl_get_privatekey($authConfig->private_key);

            // Create the token header
            $header = json_encode([
                'typ' => 'JWT',
                'alg' => 'RS256'
            ]);

            // Get seconds since 1 January 1970
            $time = time();



            // Allow 1 minute time deviation between client en server (not sure if this is necessary)
            $start = $time - 60;

            $end = $start + 3600;
        
            // Create payload
            $payload = json_encode([
                "iss" => $authConfig->client_email,
                "scope" => "https://www.googleapis.com/auth/firebase.messaging",
                "aud" => "https://oauth2.googleapis.com/token",
                "exp" => $end,
                "iat" => $start
            ]);

            // Encode Header
            $base64UrlHeader = base64UrlEncodemycode($header);

            // Encode Payload
            $base64UrlPayload = base64UrlEncodemycode($payload);

            // Create Signature Hash
            $result = openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $secret, OPENSSL_ALGO_SHA256);

            // Encode Signature to Base64Url String
            $base64UrlSignature = base64UrlEncodemycode($signature);

            // Create JWT
            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            //-----Request token, with an http post request------
            $options = array('http' => array(
                'method'  => 'POST',
                'content' => 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion='.$jwt,
                'header'  => "Content-Type: application/x-www-form-urlencoded"
            ));
            $context  = stream_context_create($options);
            $responseText = file_get_contents("https://oauth2.googleapis.com/token", false, $context);

             $response = json_decode($responseText);

     return $response->access_token;
      
    }
    
    public static function new_notification_firbase_hits_ivent($request_type,$session_id,$astrologer_id,$fullname,$mobile='') {
         
         
        $auth_token=self::generateOAuthToken(); 
        
        $auth_id = $request_type;
        $room_id = $session_id;
        $type = strtolower($request_type);
        $astroID = $astrologer_id;

        $call_details_new= CallChatRequest::where(
            "request_session_id",
            $room_id
        )->first();


        $user_astrologer = User::where('id',$astroID)->first();
        $user_datas = User::where('id',$call_details_new->user_id)->first();
        $astrologer_mobile=$user_astrologer->mobile;
        $user_mobile=$user_datas->mobile;
        if(!empty($mobile)){
         $user_mobile=$mobile;
        }
        $countycode=$user_datas->country_code;
       
        if($auth_id == "Chat"){
                   $curl = curl_init();

                    curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.exotel.com/v1/Accounts/svngstripandwire2/Calls/connect.json',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => 'From='.$astrologer_mobile.'&CallerId=01140846491&Url=http%3A%2F%2Fmy.exotel.com%2Fastrofree1%2Fexoml%2Fstart_voice%2F865869',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Basic YTM2NjUxYjMxZDg3YjVjZjMyZDY2ZTVkYzhhMmUxYmY0YjA4MDVmY2I2NWZiY2M5OmE0OTliYjE5NzgzMjA5YWVkNjc2MGNlODZlNTBiY2ZiMjI3YTcwNjIzYWJlZTgyNg==',
                        'accept: application/json',
                        'Content-Type: application/x-www-form-urlencoded'
                    ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    

        }

    

    if($auth_id == "Calling"){
    
        $show_time=DB::select("SELECT  round(ud.balance_amount/ccr.astro_call_chagre)*60 as call_time FROM call_chat_request as ccr
                        LEFT JOIN users_details as ud on ud.user_id=ccr.user_id
                        WHERE ccr.request_session_id='$room_id'"); 
         $call_time_main=$show_time[0]->call_time;                
        	  if($call_time_main >14400){
            $call_time_main=14400;
         }
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.exotel.com/v1/Accounts/svngstripandwire2/Calls/connect.json',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => 'From='.$astrologer_mobile.'&To='.$user_mobile.'&CallerId=01141169187&StatusCallback=https%3A%2F%2Fastroera.in%2Fapi%2Fsave_exotel_status&Record=true&RecordChannels=dual&StatusCallbackEvents[0]=terminal&StatusCallbackEvents[1]=answered&TimeLimit='.$call_time_main.'&CustomField='.$room_id,
          CURLOPT_HTTPHEADER => array(
            'Authorization: Basic YTM2NjUxYjMxZDg3YjVjZjMyZDY2ZTVkYzhhMmUxYmY0YjA4MDVmY2I2NWZiY2M5OmE0OTliYjE5NzgzMjA5YWVkNjc2MGNlODZlNTBiY2ZiMjI3YTcwNjIzYWJlZTgyNg==',
            'accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
          ),
        ));
       
        $response = curl_exec($curl);
         	
        curl_close($curl);
       // echo $response;
        
        

    }







        $name = $fullname;
        
        
        if($auth_id == "Chat"){
            $channel_id = "chats_channel";
        }else if($auth_id == "Calling"){
            $channel_id = "call_channel";
        }else{
            $channel_id = "video_channel";
        }
        
        $user_name = $call_details_new->user_name;
        
        $user = User::where('id',$call_details_new->user_id)->first();
        
        if($user->image != null){
            
           $user_image = image_url($user->image,'/public/cms-images/user-images/');
            
        }else{
            
            $user_image = "https://astroera.in/public/cms-images/user-images/fbybqkaDFIxlQ7qFFIWMvrUeOqcWT9L52MdRp8Dv.jpg";
        }

        $MobileDevices = MobileDevices::where("user_id", $astroID)
            ->where("status", "1")
            ->orderBy("id", "desc")
            ->get();

        foreach ($MobileDevices as $mobileDevicess) {
            
                   if($mobileDevicess->device_platform == 2){
                       
                    //   for ios
                    
                     $curl = curl_init();
                    
                    curl_setopt_array($curl, array(
                      CURLOPT_URL => 'https://fcm.googleapis.com/v1/projects/astroera-2fcd3/messages:send?access_token='.$auth_token,
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => '',
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 0,
                      CURLOPT_FOLLOWLOCATION => true,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => 'POST',
                      CURLOPT_POSTFIELDS =>'{
                            "message": {
                              "token": "'.$mobileDevicess->token.'",
                               "notification":{
                                    "title": "'.$auth_id.' Consult",
                                    "body": "New Consult Request",
                              },
                              "apns": {
                                    "payload": {
                                      "aps": {
                                        "alert": {
                                          "title": "'.$auth_id.' Consult",
                                          "body": "New Consult Request"
                                        },
                                        "sound": "default"
                                      }
                                    }
                                  },
                             "data": {
                                "title": "'.$auth_id.' Consult",
                                "body": "New Consult Request",
                                "astro_id":"'.$astroID.'",
                                "user_name":"'.$name.'",
                                "session_id":"'.$room_id.'",
                                "channel_id" : "'.$channel_id.'",
                                "type":"'.$type.'",
                                "user_id":"'.$call_details_new->user_id.'",
                                "token":"'.$mobileDevicess->token.'",
                                "user_photo":"'.$user_image.'",
                               }
                            }
                          }',
                      CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer $auth_token",
                        'Content-Type: application/json'
                      ),
                    ));
                    
                    $response = curl_exec($curl);
                    
                    curl_close($curl);
                       
                   }else if($mobileDevicess->device_platform == 1){
                     
                    //  for android
                     
                    $curl = curl_init();
                    
                    curl_setopt_array($curl, array(
                      CURLOPT_URL => 'https://fcm.googleapis.com/v1/projects/astroera-2fcd3/messages:send?access_token='.$auth_token,
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => '',
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 0,
                      CURLOPT_FOLLOWLOCATION => true,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => 'POST',
                      CURLOPT_POSTFIELDS =>'{
                            "message": {
                              "token": "'.$mobileDevicess->token.'",
                            "data": {
                    
                          "title": "'.$auth_id.' Consult",
                          "body": "New Consult Request",
                          "astro_id":"'.$astroID.'",
                          "user_name":"'.$name.'",
                          "session_id":"'.$room_id.'",
                          "channel_id" : "'.$channel_id.'",
                          "type":"'.$type.'",
                          "user_id":"'.$call_details_new->user_id.'",
                          "token":"'.$mobileDevicess->token.'",
                          "user_photo":"'.$user_image.'",
                        }
                            }
                          }',
                      CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer $auth_token",
                        'Content-Type: application/json'
                      ),
                    ));
                    
                    $response = curl_exec($curl);
                    
                    curl_close($curl);
                    // echo $response;

                   }else{
                       
                        $curl = curl_init();
                    
                    curl_setopt_array($curl, array(
                      CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => '',
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 0,
                      CURLOPT_FOLLOWLOCATION => true,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => 'POST',
                      CURLOPT_POSTFIELDS =>'{
                        "to":"'.$mobileDevicess->token.'",
                        "data": {
                          "user_photo": "https://astroera.in/public/cms-images/user-images/fbybqkaDFIxlQ7qFFIWMvrUeOqcWT9L52MdRp8Dv.jpg",
                          "user_name": "'.$name.'",
                          "astro_id":"'.$astroID.'",
                          "type":"'.$type.'",
                          "call_status":"'.$call_details_new->request_status.'",
                          "session_id":"'.$room_id.'"
                        }
                      }',
                      CURLOPT_HTTPHEADER => array(
                        'Authorization: key=AAAAZf7FN50:APA91bEA-PGU4fD1Ju8vUzk5ecTPoQ7F8wsVWFmPRDSVqVPZz-RENQUoIWni63TXVoup8tV8fQCllVgXFeW2MniLPbV54CE-iggF1pJkKMxz62FELUL0_VniyytUFjzAsjOR5-1pbk-_',
                        'Content-Type: application/json',
                        'key: AIzaSyDvi0b-XiqEwrGqTWEGcmqt2so7xHXsCgE'
                      ),
                    ));
                    
                    $response = curl_exec($curl);
                    
                    curl_close($curl);
                       
                   } 

                   if($response){
    
                    $storeToDB = DB::select("INSERT INTO `firestore_update_details` (`request_session_id`,`firestore_response`,`status`,`sender`,`token`) VALUES ('$call_details_new->request_session_id','$response','$call_details_new->request_status','user','$mobileDevicess->token')" );

               }
                    
                  
        }
    }


    


  public static function SwitchSessionEvents($expert_id,$userid,$name,$image,$sessionid,$status,$switch_to,$expert_name,$expert_profile,$meet_id='',$newsessionid=''){
     
    $curl = curl_init();
    $url = "https://firestore.googleapis.com/v1/projects/astroeranew/databases/(default)/documents/switch_session/$expert_id?updateMask.fieldPaths=session_id&updateMask.fieldPaths=status&updateMask.fieldPaths=switch_to&updateMask.fieldPaths=user_id&updateMask.fieldPaths=meet_id&updateMask.fieldPaths=name&updateMask.fieldPaths=image&updateMask.fieldPaths=new_session&updateMask.fieldPaths=expert_name&updateMask.fieldPaths=expert_profile";
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'PATCH',
      CURLOPT_POSTFIELDS =>'
            {
                "name": "projects/astroeranew/databases/(default)/documents/switch_session/'.$expert_id.'",
                "fields": {
                    "session_id": {
                        "stringValue": "'.$sessionid.'"
                    },
                    "status": {
                        "stringValue": "'.@$status.'"
                    },
                    "switch_to": {
                        "stringValue": "'.@$switch_to.'"
                    },
                    "user_id": {
                        "stringValue": "'.$userid.'"
                    },
                    "name": {
                        "stringValue": "'.$name.'"
                    },
                    "image": {
                        "stringValue": "'.$image.'"
                    },
                    "meet_id": {
                        "stringValue": "'.$meet_id.'"
                    },
                    "new_session": {
                        "stringValue": "'.$newsessionid.'"
                    },
                    "expert_name": {
                        "stringValue": "'.$expert_name.'"
                    },
                    "expert_profile": {
                        "stringValue": "'.$expert_profile.'"
                    }
                },
                "createTime": "'.date('Y-m-d\TH:i:s.v\Z').'",
                "updateTime": "'.date('Y-m-d\TH:i:s.v\Z').'"
               }
             ',
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
              ),
            ));
            
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
}
	
 public static function PushNOtification($fcmtoken, $title, $message,$imageUrl='https://astroera.in/public/frontend/img/Favico.ico', $type = null , $url = '') {
    $curl = curl_init();
    $auth_token = self::generateOAuthToken(); 

    $chtype = !empty($type) ? $type : '';
    $message = !empty($message) ? $message : 'image';

	 if($fcmtoken['device_platform'] == 2){
		 	
	$payload = [
    "message" => [
        "token" => $fcmtoken['token'],
        "notification" => [
            "title" => $title,
            "body" => $message
        ],
        "data"=>[
            'image'=>$imageUrl,
            "title" => $title,
            "body" => $message,
            'type' => (string)$chtype,
            'url' => $url,
            'click_action' => 'open'
        ],
        "android" => [
            "notification" => [
                "icon" => "ic_notification",
                "color" => "#FFFFFF",
                "sound" => "default",
                "channel_id" => "Notification",
                "image" => $imageUrl,
                "click_action" => "open"
            ],
            "priority" => "HIGH"
        ],
        "apns" => [
            "payload" => [
                "aps" => [
                    "alert" => [
                        "title" => $title,
                        "body" => $message
                    ],
                    "sound" => "default"
                ]
            ]
        ]
    ]
];


		
		 
	 }else{
	 $payload = [
        "message" => [
            "token" => $fcmtoken['token'],
			  "data"=>[
                'image'=>$imageUrl,
                "title" => $title,
                "body" => $message,
                'type' => (string)$type,
                'url' => $url,
              ],
              "notification" => [
                "title" => $title,
                "body" => $message
				
            ]
        ]
    ];
	 }
    
    $jsonData = json_encode($payload, JSON_UNESCAPED_UNICODE);
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://fcm.googleapis.com/v1/projects/astroeranew/messages:send?access_token=' . $auth_token,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $jsonData, // Correct JSON
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $auth_token",
            'Content-Type: application/json'
        )
    ));

    $response = curl_exec($curl);

  
    curl_close($curl);
	 return true;
}

  public static function PushNOtificationAuthdata($fcmtoken,$array=[]) {
	 
    $curl = curl_init();
    $auth_token = self::generateOAuthToken(); 


    // Prepare the payload correctly using an array and json_encode
	  $imageUrl='';
	  $title='';
	  $message='image';
	   $type='';
	  $senderid='';
      $url = '';
      
	  
	  if(!empty($array['image'])){
	  	  $imageUrl=$array['image'];
	  }
	  
	   if(!empty($array['type'])){
	  	  $type=$array['type'];
	  }
	  
	    if(!empty($array['senderid'])){
	  	  $senderid=$array['senderid'];
	  }
	  
	  if(!empty($array['url'])){
	  	  $url=$array['url'];
	  }
	  
	  
	  if(!empty($array['title'])){
	  	  $title=$array['title'];
	  }
	  if(!empty($array['message'])){
	  	  $message=$array['message'];
	  }


	 if($fcmtoken['device_platform'] == 2){
		 	
	$payload = [
    "message" => [
        "token" => $fcmtoken['token'],
			"data"=>[
					'image' => (string)$imageUrl,
					'senderid' => (string)$senderid,
					'type' => (string)$type,
                    "title" => $title,
                    "body" => $message,
                    'url' => $url,
                    'click_action' => 'open'
				],
        "notification" => [
            "title" => $title,
            "body" => $message
		
        ],
		"android" => [
            "notification" => [
                "icon" => "ic_notification",
                "color" => "#FFFFFF",
                "sound" => "default",
                "channel_id" => "Notification",
                "image" => $imageUrl,
                "click_action" => "open"
            ],
            "priority" => "HIGH"
        ],
        "apns" => [
            "payload" => [
                "aps" => [
                    "alert" => [
                        "title" => $title,
                        "body" => $message
                    ],
                    "sound" => "default"
                ]
            ]
        ]
    ]
];


		
		 
	 }else{
	 $payload = [
        "message" => [
            "token" => $fcmtoken['token'],
			  "data"=>[
					'image' => (string)$imageUrl,
					'senderid' => (string)$senderid,
					'type' => (string)$type,
                    "title" => $title,
                    "body" => $message,
                    "url" => $url
				]
        ]
    ];
	 }
    
    $jsonData = json_encode($payload, JSON_UNESCAPED_UNICODE);
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://fcm.googleapis.com/v1/projects/astroeranew/messages:send?access_token=' . $auth_token,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $jsonData, // Correct JSON
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $auth_token",
            'Content-Type: application/json'
        )
    ));

    $response = curl_exec($curl);
	//echo "<pre>";print_r($response);
  
    curl_close($curl);
	 return true;
}
}
