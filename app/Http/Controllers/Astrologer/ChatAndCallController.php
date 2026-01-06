<?php

namespace App\Http\Controllers\Astrologer;

use App\Http\Controllers\Controller;
use App\Models\CallChatRequest;
use App\Models\ConsultRemedies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FireBaseActionController;


class ChatAndCallController extends Controller
{
    //
        public function SendRemedis(Request $request,$consultid){
        $validator = Validator::make($request->all(), [
            'expert_id' => 'required|exists:users,id',
           
        ]);
            try {
                if ($validator->fails()) {
                    return errorResponse($validator->errors());
                }
                $checkconsultdata=CallChatRequest::where('request_session_id',$consultid)
                ->where('expert_id',$request->expert_id)
                ->first();
                if(!$checkconsultdata){
                    return SimpleResponse(200,false,'Invalid consultid');
                }   
               
					 $getfcmtoken=getFcmToken($checkconsultdata->user_id);
				
				if($getfcmtoken){
                    $type = 'Remedies';
					$url = 'astroera://remedies?callData='.$consultid;
					 $expertdata = DB::select("SELECT name FROM `users` WHERE `id` = ?", [$request->expert_id]);

					$expertName = !empty($expertdata) ? $expertdata[0]->name : $consultid;

					FireBaseActionController::PushNOtification(
						$getfcmtoken,
						'New Remedies',
						'You have a new remedy suggestion from ' . $expertName,
						'',
						$type,
						$url
					);

				}
				
				
                $data = ConsultRemedies::create(
                  
                    [
						'consult_it' => $consultid,
                        'remedies' => $request->remedies,
                        'pooja' => $request->suggeested_pooja
                    ]
                );                
                return ApiResponse(200,true,'success',$data);

            } catch (\Throwable $th) {
                return InternalError($th->getMessage());
            }

    }
	  public function UpdateRemedies(Request $request,$consultid){
        $validator = Validator::make($request->all(), [
            'expert_id' => 'required|exists:users,id',

        ]);
            try {
                if ($validator->fails()) {
                    return errorResponse($validator->errors());
                }
                $checkconsultdata=CallChatRequest::where('request_session_id',$consultid)
                ->where('expert_id',$request->expert_id)
                ->first();
                if(!$checkconsultdata){
                    return SimpleResponse(200,false,'Invalid consultid');
                }   
                $data = ConsultRemedies::where('consult_it', $consultid)->first();
                if($data){
                    $suggeested_pooja=$request->suggeested_pooja;
                    $newpooja=$data->pooja;
                    if(!empty($newpooja)){

                        $newpooja = array_values(array_unique(array_merge($newpooja, $suggeested_pooja)));
                    }else{
                        $newpooja = $suggeested_pooja;

                    }


                    
                    $suggested_remedies=$request->remedies;
                   
                    $newremedies=$data->remedies;
                   
                    if(!empty($newremedies)){
                  

                        $newremedies = array_merge($newremedies, $suggested_remedies ?? []);

                    }else{
                    $newremedies=$request->remedies;

                    }



                    $data->remedies=$newremedies;
                    $data->pooja=$newpooja;

                    $data->save();
                return ApiResponse(200,true,'success',$data);

                }else{
				$data = ConsultRemedies::create([
						'consult_it' => $consultid,  // Missing comma fixed
						'remedies' => $request->remedies,
						'pooja' => $request->suggeested_pooja
					]);
  
					
					   return ApiResponse(200,true,'success',$data);
				}
               
                   
                
                             

            } catch (\Throwable $th) {
                return InternalError($th->getMessage());
            }

    }
}
