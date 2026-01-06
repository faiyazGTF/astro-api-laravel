<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Payment;
use App\Models\RechargePackage;
use App\Models\User\User;
use App\Models\User\UsersDetail;
use App\Models\User\WalletsModel;
use App\Models\CallChatRequest;
use App\Http\Controllers\FireBaseActionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;


class RechargePackageController extends Controller
{

    private function getAstroDetails($astroid){

        $expertdata = User::select('users_details.flags','users_details.astro_video_charges','users_details.video_commission','users_details.disc_video_charge','users_details.call_commission','users.image','users_details.chat_commission','users_details.disc_call_charge','users_details.astro_call_charges','users_details.is_promotional_accept','users_details.astro_chat_charges','users_details.disc_chat_charge','users_details.availability','users.id','users.name')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $astroid)->first();
	
        if($expertdata){
            $expertdata->image= image_url($expertdata->image,'/public/cms-images/user-images/'); // Append the base URL

            return $expertdata;
        }
        return false;
    }

    public static function UserRechargeTopopOnActivesession($user_id){
        try {
          $activesession = CallChatRequest::where("user_id", $user_id)->where('request_status',1)->first();
        
        $astro_id=$activesession->expert_id;
        $curl = curl_init();
        $getuserdata=User::select('users.user_type','users_details.balance_amount','users.id','users.image')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id',$user_id)->first();
        $user_image=!empty(!$getuserdata->image) ? "https://astro-api.iqsetters.in/public/cms-images/user-images/$getuserdata->image": "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
  
  
        $url="https://firestore.googleapis.com/v1/projects/astroeranew/databases/(default)/documents/astrologer_consult/$astro_id?updateMask.fieldPaths=user_profile&updateMask.fieldPaths=start_time&updateMask.fieldPaths=total_waitlist_count&updateMask.fieldPaths=user_name&updateMask.fieldPaths=consult_id&updateMask.fieldPaths=user_wallet_balance&updateMask.fieldPaths=user_id&updateMask.fieldPaths=consult_type&updateMask.fieldPaths=consult_status";
        $all_users = CallChatRequest::where("expert_id", $astro_id)->where('request_status',20)->count();
        $max_end_time=getmaxExpectedTime($activesession->request_session_id);
        // dd($max_end_time['data']['duration']);
  
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
      "name": "projects/astroeranew/databases/(default)/documents/astrologer_consult/'.$astro_id.'",
      "fields": {
          "user_profile": {
              "stringValue": "'.$user_image.'"
          },
          "start_time": {
              "stringValue": "'.$activesession->astro_start_time.'"
          },
          "total_waitlist_count": {
              "integerValue": "'.($all_users ? $all_users : 0).'"
          },
          "user_name": {
              "stringValue": "'.$activesession->user_name.'"
          },
          "consult_id": {
              "stringValue": "'.$activesession->request_session_id.'"
          },
          "user_wallet_balance": {
              "integerValue": '.$getuserdata->balance_amount.'
          },
          "user_id": {
              "stringValue": "'.$user_id.'"
          },
          "consult_type": {
              "stringValue": "'.$activesession->request_type.'"
          },
          "consult_status": {
              "stringValue": "active"
          },
          "max_end_time": {
      "integerValue": ' . (isset($max_end_time['data']['duration']) ? $max_end_time['data']['duration'] : 1) . '
  }
  
      },
      "createTime": "'.date('Y-m-d\TH:i:s.v\Z').'",
      "updateTime": "'.date('Y-m-d\TH:i:s.v\Z').'"
      }',
  CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json'
  ),
  
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                  ),
                ));
                
                $response = curl_exec($curl);
                curl_close($curl);  
        } catch (\Throwable $th) {
         return $th->getMessage();
        }
        
    }
    
    public static function CheckOurRequestData($user_id,$user_kundali_request_info_id=0,$order_id,$userdata,$location,$product_type,$amount,$shippingCharge,$taxPercentage,$taxAmount,$device_type,$discount=0,$coupon_data=''){
        $requestdata=new \stdClass();
        $requestdata->user_id = $user_id;
        $requestdata->user_kundali_request_info_id = $user_kundali_request_info_id; // for custom amount 
        $requestdata->order_id = $order_id;
        $requestdata->name = $userdata->name;
        $requestdata->mobile = $userdata->mobile;
        $requestdata->email = $userdata->email;
        $requestdata->address = $location['state']['name'];
        $requestdata->state_id = $location['state']['id'];
        $requestdata->country_id = $location['country']['id'];
        $requestdata->postcode = $location['postcode'];;
        $requestdata->product_type =$product_type;
        $requestdata->amount = $amount;
        $requestdata->shipping_charge = $shippingCharge;
        $requestdata->tax_percentage = $taxPercentage;
        $requestdata->tax_amount = $taxAmount;
        $requestdata->discount =$discount;
        $requestdata->coupon_data =$coupon_data;
        $requestdata->order_status ='Pending';
        $requestdata->device_type =$device_type;
        return $requestdata;
    }
    public function CustomeRecharge(Request $request){
     

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'ip' => 'required',
            'device_type' => 'required|in:1,2',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode'=>403,
                'status'=>false,
                'message'=>'Please Fill Mandatory fields',
                'errors'=>$validator->errors()
            ]);
        }

        $ip=$request->ip;
      
        $latitute="26.2037247";
        $longtitute="78.1573628";

        $geolocation = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));

        if(!empty($geolocation->loc)){
            $explodelcoaation=explode(',',$geolocation->loc);
            $latitute=$explodelcoaation[0];
            $longtitute=$explodelcoaation[1];

        }
      


        $userdata = User::where('id', $request->user_id)->first();
        if($userdata){
            $currency = "INR";
            $lat = $latitute;
            $long = $longtitute;
            $order_id = 'RG' . time() . rand(100, 999);
            $product_type = 'recharge';
            $user_id = $request->user_id;
            $amount = $request->amount;
            $shippingCharge = 0;
            $taxPercentage = 18;
            $taxAmount = round(($amount * $taxPercentage) / 100, 2); // calculate tax
            $totalAmount = $amount + $taxAmount + $shippingCharge; 
            $location=getLocationByLatLong($lat,$long);
            $requestdata=$this->CheckOurRequestData($user_id,0,$order_id,$userdata,$location,$product_type,$amount,$shippingCharge,$taxPercentage,$taxAmount,$request->device_type);
            $checkoutresult =Checkout::addRecord($requestdata);
            if($checkoutresult){
                $paymenmentreques=new \stdClass();
                $paymenmentreques->user_id = $user_id;
                $paymenmentreques->order_id = $order_id;
                $paymenmentreques->total_amount = $totalAmount;
                $paymenmentreques->payment_status = 'Pending';
                $paymenmentreques->gateway_name = 'razorpay';
                $paymenmentreques->currency = $currency;
                $paymenmentreques->device_details = $request->device_type;
                $paymenresult=Payment::saveRecord($paymenmentreques);
                if( !empty($paymenresult->status) && $paymenresult->status=='created'){
					
					  $talktimeamount=$request->amount;
                    $getplandata=RechargePackage::where('package_amount',$talktimeamount)->first();
                    if($getplandata){
                        $talktimeamount=$getplandata->talk_time_amount;
                    }
					
                    return response()->json([
                        'statusCode'=>200,
                        'status'=>true,
                        'message'=>'success',
                        'data'=>[
                            'key'=>env('RAZORPAY_KEY'),
                            'order_id'=>$order_id,
                            'amount'=>$amount,
                            'after_tax'=>$totalAmount,
                            'taxAmount'=>$taxAmount,
                            'offer_amount'=>0,
                            'gst'=>$taxPercentage,
                            'order_id'=>$order_id,
                            'gateway_response'=>$paymenresult,
							'get_amount'=>$talktimeamount
                        ]
                    ]);
                }
                return response()->json([
                    'statusCode'=>500,
                    'status'=>false,
                    'message'=>$paymenresult,
                ]);
               
            }            
        }
        return response()->json([
            'statusCode'=>500,
            'status'=>false,
            'message'=>'something went wrong ',
        ]);
    }


    private function handleCompletedPayment($checkout, $payment_id, $financial_year, $lastInvoice)
    {    


      
        if ($checkout->product_type == 'recharge') {
            
            $pay_amount = $checkout->amount;

            $obj_value = UsersDetail::where('user_id', $checkout->user_id)->first();
            if ($checkout->user_kundali_request_info_id != 0) {
                $Bids = Checkout::leftjoin('recharge_packages', 'recharge_packages.id', '=', 'checkouts.user_kundali_request_info_id')
                    ->select('checkouts.user_id', 'checkouts.id', 'recharge_packages.talk_time_amount', 'recharge_packages.id','recharge_packages.offer_amount')
                    ->where('checkouts.order_id', $checkout->order_id)
                    ->first();
                $pay_amount = $Bids->talk_time_amount;
                $user_id_main = $Bids->user_id;
            }
            $user_id_main = $checkout->user_id;

            $amount = 0;
            if (!empty($obj_value->balance_amount)) {
                $amount = $obj_value->balance_amount;

            }
            
            $obj = UsersDetail::where('user_id', $user_id_main)->first();
              
            DB::table('user_transaction_ledeger')->insert([
                'usertype' => 'user',
                'user_id' => $user_id_main,
                'current_balance' => $obj->balance_amount,
                'new_balance' => $pay_amount + $amount,
                'top_up_balance' => $pay_amount,
                'transaction_id'=>$payment_id,
                'conditions'=>'w2',
                'created_at' => date('Y-m-d H:i:s', time()),
                'transaction_type' => 'credits',
            ]);

            $obj->balance_amount = $pay_amount + $amount;
            $obj->save();
        }
        $obj1 = Payment::where('order_id', $checkout->order_id)->first();
        $obj1->user_id = $checkout->user_id;
        $obj1->payment_id = $payment_id;
        $obj1->total_amount = $checkout->amount + $checkout->tax_amount;
        $obj1->currency = 'INR';
        $obj1->payment_status = 'Completed';
        $obj1->post_meta = '100669';
        $obj1->gateway_name = 'razorpay';
        $obj1->financial_year = $financial_year;
        $obj1->invoice_no = !empty($lastInvoice) ? $lastInvoice->invoice_no + 1 : 1;
        $obj1->save();
        return $obj1;
    }


  public function RazorpayWebhooks(Request $request){
        
        
       try {
        $data = json_encode($request->all());
      
        $storeToDB = DB::select("INSERT INTO `demos` (`payment_type`,`data`) VALUES ('iqapicall_payment','$data')");
        $newData = json_decode($data);
        @$order_id = $newData->payload->payment->entity->notes->key1;
     
        $payment = Payment::where('order_id', $order_id)->lockForUpdate()->first();
        $checkout = Checkout::where('order_id', $order_id)->first();

         if ($payment->payment_status !== "Completed" && $checkout->order_status !== "Completed") {
             if ($newData->payload->payment->entity->status == "captured") {
                $status = "Completed";
            } elseif ($newData->payload->payment->entity->status == "authorized") {
                $status = "Completed";
            } else {
                $status = "Failed";
            }
			 
            $payment_id = $newData->payload->payment->entity->acquirer_data->rrn ?? 'testfz';

              
                if ($checkout->order_status !== "Completed") {
          
                    $checkout->order_status = $status;
                    if($checkout->save()){
                        $date_year = ($payment->created_at)->format('Y');
                        $date_month = ($payment->created_at)->format('m');
                        $financial_year = ($date_month < 4) ? ($date_year - 1) . '-' . $date_year : $date_year . '-' . ($date_year + 1);
                        $lastInvoice = Payment::where('financial_year', $financial_year)->where('payment_status', 'Completed')->orderBy('id', 'DESC')->first();
                        $response= $this->handleCompletedPayment($checkout, $payment_id, $financial_year, $lastInvoice);


                        $userId = $checkout->user_id;
        
                        $user = User::find($userId);

                        $ongoingSession = CallChatRequest::where('user_id', $userId)
                        ->whereIn('request_status', [2, 20])
                            ->orderBy('id', 'desc')
                            ->first();

                        if ($ongoingSession) {
                            $expertId = $ongoingSession->expert_id;
                        
                            $astrodata = $this->getAstroDetails($expertId);

                            $userImage = !empty($user->image)
                                ? "https://astro-api.iqsetters.in/public/cms-images/user-images/{$user->image}"
                                : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";

                            $maxEndTimeData = getmaxExpectedTime($ongoingSession->request_session_id);

                            if ($maxEndTimeData['status']) {
                                $end_time = $maxEndTimeData['data']['max_time'];

                                FireBaseActionController::AstrologerConsultUpdate(
                                    $expertId,
                                    $userId,
                                    $ongoingSession->user_name,
                                    $userImage,
                                    $user->users_detail->balance_amount ?? 0,
                                    $ongoingSession->request_type,
                                    $ongoingSession->request_session_id,
                                    'active',
                                    $ongoingSession->astro_start_time,
                                    $end_time
                                );

                                // Update Socket Server Timer
                                try {
                                    Http::post(env('SOCKET_SERVER_URL', 'http://localhost:65282') . '/update-server-timer', [
                                        'room' => $ongoingSession->request_session_id,
                                        'endTime' => $end_time
                                    ]);
                                } catch (\Throwable $e) {
                                    // Log error but don't stop execution
                                    \Log::error("Socket Timer Update Failed: " . $e->getMessage());
                                }
                            }
                        }

                        return $response;
                    }
                }
         }

         return (["message" => "Payment failed Something went wrong"]);
       } catch (\Throwable $th) {
        //throw $th;
       }

    }




    public function RechargeByPlan(Request $request){   
        try {
           
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'ip' => 'required',

            'plan_id' => 'required|exists:recharge_packages,id',
          
            'device_type' => 'required|in:1,2',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode'=>403,
                'status'=>false,
                'message'=>'Please Fill Mandatory fields',
                'errors'=>$validator->errors()
            ]);
        }


        $ip=$request->ip;
      
        $latitute="26.2037247";
        $longtitute="78.1573628";

        $geolocation = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));

        if(!empty($geolocation->loc)){
            $explodelcoaation=explode(',',$geolocation->loc);
            $latitute=$explodelcoaation[0];
            $longtitute=$explodelcoaation[1];

        }


        $userdata = User::where('id', $request->user_id)->first();
        $checkplan = RechargePackage::leftjoin('taxes', 'taxes.id', '=', 'recharge_packages.tax_id')->where('recharge_packages.id', $request->plan_id)->first();
        if($userdata && $checkplan){
            $currency = "INR";
            $lat = $latitute;
            $long = $longtitute;
            $order_id = 'RG' . time() . rand(100, 999);
            $product_type = 'recharge';
            $user_id = $request->user_id;
            $amount = $request->amount;
            $shippingCharge = 0;
            $taxPercentage = $checkplan->tax_value;
            $amount = $checkplan->package_amount;
            $taxAmount = round(($amount * $taxPercentage) / 100, 2);
            $totalAmount = $amount + $taxAmount + $shippingCharge; 
  
            $location=getLocationByLatLong($lat,$long);
            $requestdata=$this->CheckOurRequestData($user_id,$request->plan_id,$order_id,$userdata,$location,$product_type,$amount,$shippingCharge,$taxPercentage,$taxAmount,$request->device_type);
            $checkoutresult =Checkout::addRecord($requestdata);
            if($checkoutresult){
                $paymenmentreques=new \stdClass();
                $paymenmentreques->user_id = $user_id;
                $paymenmentreques->order_id = $order_id;
                $paymenmentreques->total_amount = $totalAmount;
                $paymenmentreques->payment_status = 'Pending';
                $paymenmentreques->gateway_name = 'razorpay';
                $paymenmentreques->currency = $currency;
                $paymenmentreques->device_details = $request->device_type;
                $paymenresult=Payment::saveRecord($paymenmentreques);
       
                if( !empty($paymenresult->status) && $paymenresult->status=='created'){
                    return response()->json([
                        'statusCode'=>200,
                        'status'=>true,
                        'message'=>'success',
                        'data'=>[
                            'key'=>env('RAZORPAY_KEY'),
                            'order_id'=>$order_id,
                            'amount'=>$amount,
                            'after_tax'=>$totalAmount,
                            'taxAmount'=>$taxAmount,
                            'offer_amount'=>0,
                            'gst'=>$taxPercentage,
                            'order_id'=>$order_id,
                            'gateway_response'=>$paymenresult,
							 'get_amount'=>$checkplan->talk_time_amount


                        ]
                    ]);
                }
                return response()->json([
                    'statusCode'=>500,
                    'status'=>false,
                    'message'=>$paymenresult,
                ]);
               
            }            
        }
        return response()->json([
            'statusCode'=>500,
            'status'=>false,
            'message'=>'something went wrong ',
        ]);

        } catch (\Throwable $th) {
                    return InternalError($th->getMessage());

        }
        
    }
	
           public function CheckoutRecharge(Request $request)
	{
		// Step 1: Validate input
		$validator = Validator::make($request->all(), [
			'user_id' => 'required|exists:users,id',
			'type' => 'required|in:custom,plan',
			'plan_id' => 'required_if:type,plan|exists:recharge_packages,id',
			'amount' => 'required_if:type,custom|numeric|min:1',
		]);

		if ($validator->fails()) {
			return response()->json([
				'statusCode' => 403,
				'status' => false,
				'message' => 'Please fill all mandatory fields',
				'errors' => $validator->errors()
			]);
		}

		// Step 2: Load user
		$user = User::find($request->user_id);
		if (!$user) {
			return response()->json([
				'statusCode' => 404,
				'status' => false,
				'message' => 'User not found'
			]);
		}

		$shippingCharge = 0;
		$talktimeAmount = 0;
		$amount = 0;

		if ($request->type == 'plan') {
			// Fetch selected plan
			$plan = RechargePackage::where('id', $request->plan_id)->first();

			if (!$plan) {
				return response()->json([
					'statusCode' => 400,
					'status' => false,
					'message' => 'Invalid plan ID'
				]);
			}

			$amount = $plan->package_amount;
			$talktimeAmount = $plan->talk_time_amount;
			$checkplan = RechargePackage::leftjoin('taxes', 'taxes.id', '=', 'recharge_packages.tax_id')->where('recharge_packages.id', $request->plan_id)->first();
            $gst = $checkplan->tax_value;
		} else {
			// custom type
			$amount = $request->amount;
			$talktimeAmount = $amount;

			// Check if custom amount matches any plan for bonus talktime
			$matchedPlan = RechargePackage::where('package_amount', $amount)->first();
			if ($matchedPlan) {
				$talktimeAmount = $matchedPlan->talk_time_amount;
			}
		}

		// Final tax + total calc
		$taxPercentage = 18;
		$taxAmount = round(($amount * $taxPercentage) / 100, 2);
		$totalAmount = $amount + $taxAmount + $shippingCharge;

		return response()->json([
			'statusCode' => 200,
			'status' => true,
			'message' => 'Checkout prepared successfully',
			'data' => [
				'user_id' => $user->id,
				'recharge_type' => $request->type,
				'amount' => $amount,
				'gst' => number_format($gst ?? 18.00, 2, '.', ''),
				'tax' => $taxAmount,
				'total_amount' => $totalAmount,
				'talktime_amount' => $talktimeAmount
			]
		]);
	}
	
	
	
    public function RechageList(Request $request){
        $result=RechargePackage::getRechargPlanList($request);
        return response()->json([
            'statusCode'=>200,
            'status'=>true,
            'message'=>'success',
            'data'=>$result
        ]);
    }
    public function getTransactionHistoryByUser($user_id)
    {
       try {
            $orders = Checkout::leftJoin('payments', 'payments.order_id', '=', 'checkouts.order_id')
            ->select('checkouts.product_type as type', 'checkouts.created_at', 'payments.order_id', 'payments.payment_id as trasaction_id', 'checkouts.amount', 'checkouts.tax_amount as GST', 'payments.payment_status as transaction_type')
            ->where('checkouts.user_id', $user_id)
            
            ->orderby('checkouts.id', 'DESC')
            ->get()
            ->map(function ($item) { 

               // if ($item->transaction_type === 'Pending') {
               //     $item->transaction_type = 'Canceled';
               // }
        
                if ($item->type=='product') {
                    $item->poojaname = Checkout::join('products', 'products.id', 'checkouts.user_kundali_request_info_id')
                    ->where('order_id', $item->order_id)
                    ->select('products.name')
                    ->first()
                    ->name ?? null; // Use null-safe operator to avoid errors
                    $item->type='Puja';
                }
				
				if (in_array($item->type, ['recharge', 'Puja']) && $item->transaction_type === 'Completed') {
					$item->invoice_url = 'https://astro-api.iqsetters.in/invoice/' . $item->order_id;
				}
				
                return $item;
            });
            
            $transactionHistory = WalletsModel::select('gifts.title as gitname','gifts.image as gift_image','wallets.product_type as type', 'wallets.created_at', 'transaction_id as order_id', 'wallets.amount', 'transaction_type')
                ->leftJoin('gifts', 'gifts.id', '=', 'wallets.transaction_id')
                ->where('wallets.user_id', $user_id)
                ->where('wallets.product_type', 'Gift Amount')
                ->where('wallets.transaction_type', 'debits')->orderby('wallets.id', 'DESC')->get()
                ->map(function ($item) { 
                // Update the image URL only if gift_image is not null
                if (!empty($item->gift_image)) {
                    $item->gift_image = image_url($item->gift_image,'/public/cms-images/gift/') ;
                }
					
				  if (in_array($item->type, ['recharge', 'Puja']) && $item->transaction_type === 'Completed') {
						$item->invoice_url = 'https://astro-api.iqsetters.in/invoice/' . $item->order_id;
					}	
					
                return $item;
            });
 
            $collection1 = collect($orders);
            $collection2 = collect($transactionHistory);
            $merged = $collection1->merge($collection2);
            $sorted = $merged->sortByDesc('created_at');
            return ApiResponse(200,true,'success',$sorted->values());
            
       } catch (\Throwable $th) {
        return InternalError($th->getMessage());
       }
    }
	
	
	
	public function getSinglePlan($planid){
        
      

      



        $checkplan = RechargePackage::leftjoin('taxes', 'taxes.id', '=', 'recharge_packages.tax_id')->where('recharge_packages.id',$planid)->first();
        if($checkplan){
            $currency = "INR";

            $order_id = 'RG' . time() . rand(100, 999);
            $product_type = 'recharge';
            $amount = $checkplan->package_amount;
            $shippingCharge = 0;
            $taxPercentage = $checkplan->tax_value;
            $amount = $checkplan->package_amount;
            $taxAmount = round(($amount * $taxPercentage) / 100, 2);
            $totalAmount = $amount + $taxAmount + $shippingCharge; 
  

              return response()->json([
                        'statusCode'=>200,
                        'status'=>true,
                        'message'=>'success',
                        'data'=>[
                            'amount'=>$amount,
                            'after_tax'=>$totalAmount,
                            'taxAmount'=>$taxAmount,
                            'offer_amount'=>0,
                            'gst'=>$taxPercentage,
                            'order_id'=>$order_id,
                        ]
                    ]);
               
                      
        }
        return response()->json([
            'statusCode'=>500,
            'status'=>false,
            'message'=>'something went wrong ',
        ]);

        
    }
}
