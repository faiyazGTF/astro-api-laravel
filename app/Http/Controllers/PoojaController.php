<?php

namespace App\Http\Controllers;

use App\Http\Controllers\User\RechargePackageController;
use App\Models\Checkout;
use App\Models\CouponModel;
use App\Models\Payment;
use App\Models\PoojaCategory;
use App\Models\PoojaModel;
use App\Models\TaxesModel;
use App\Models\User\User;
use App\Models\User\UsersDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PoojaController extends Controller
{
    public function getPoojaCategoryList(Request $request)
    {
        $model = PoojaCategory::getPoojaCategoty($request);
        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'data' => $model
        ]);
    }

    public function getPoojaList(Request $request)
    {
        $result = PoojaModel::getPoojaList($request);
        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'data' => $result
        ]);
    }
    public function getPoojaDetails($poojaId)
    {

        $result = PoojaModel::getPoojaDetails($poojaId);
        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'data' => $result
        ]);
    }
    public function BookPooja(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'pooja_id' => 'required|exists:products,id',
            'name' => 'required',

            'address' => 'required',
            'latitude' => 'required',
            'longtitude' => 'required'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ]);
        }
        try {
            $authid = $request->auth_user->id;

            $ip = '182.69.181.141';
            if ($request->ip) {
                $ip = $request->ip;
            }
            $latitute = $request->latitude;
            $longtitute = $request->longtitude;

            $geolocation = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
            $paymentcity = 'Delhi';
            if (!empty($geolocation->loc)) {
                $explodelcoaation = explode(',', $geolocation->loc);
                $paymentlatitute = $explodelcoaation[0];
                $paymentlongtitute = $explodelcoaation[1];
                $paymentcity = $geolocation->city;
            }
            $user_details = UsersDetail::where('user_id', $authid)->first();

            if (!empty($user_details->address_details) && $user_details->address_details != 'null') {
                $address_details = json_decode($user_details->address_details);
                if (!empty($address_details->lat) && !empty($address_details->lng)) {
                    $billingaddressdetails = getLocationByLatLong($address_details->lat, $address_details->lng);
                } else {
                    $billingaddressdetails = getLocationByLatLong($latitute, $longtitute);
                }
            } else {
                $billingaddressdetails = getLocationByLatLong($latitute, $longtitute);
            }


            $pooja_id = $request->pooja_id;
            $productData = PoojaModel::where('id', $pooja_id)->where('product_type', 'Service')->first();
            if ($productData) {
                $usernameupdations = User::find($authid);
                if (stripos($usernameupdations->name, 'guest') !== false) {
                    $usernameupdations->name = $request->name;
                }

                $usernameupdations->save();


                $taxData = TaxesModel::where('id', $productData->tax_id)->first();
                $currency = "INR";
                $discount_in = 0;
                $discountVal = 0;
                $max_discount = 0;
                $amount = $productData->discount_price_inr;
                $discount = round(($amount * $discountVal) / 100, 2);
                $taxPercentage = isset($taxData->tax_value) ? $taxData->tax_value : 0;
                $subTotalAmount = $amount - $discount;
                $taxAmount = round(($subTotalAmount * $taxPercentage) / 100, 2);
                $totalAmount = $subTotalAmount + $taxAmount + $productData->shipping_charge;




                $order_id = 'OD' . time() . rand(100, 999);
                $product_type = 'product';
                $device_type = $request->device_type ? $request->device_type : 1;
                $requestdata = RechargePackageController::CheckOurRequestData($authid, $pooja_id, $order_id, $usernameupdations, $billingaddressdetails, $product_type, $totalAmount, $productData->shipping_charge, $taxPercentage, $taxAmount, $device_type);
                $checkoutresult = Checkout::addRecord($requestdata);
                if ($checkoutresult) {
                    $paymenmentreques = new \stdClass();
                    $paymenmentreques->user_id = $authid;
                    $paymenmentreques->order_id = $order_id;
                    $paymenmentreques->total_amount = $totalAmount;
                    $paymenmentreques->payment_status = 'Pending';
                    $paymenmentreques->gateway_name = 'razorpay';
                    $paymenmentreques->currency = $currency;
                    $paymenmentreques->device_details = $device_type;
                    $paymenresult = Payment::saveRecord($paymenmentreques);
                    if (!empty($paymenresult->status) && $paymenresult->status == 'created') {
                        return response()->json([
                            'statusCode' => 200,
                            'status' => true,
                            'message' => 'success',
                            'data' => [
                                'key' => env('RAZORPAY_KEY'),
                                'order_id' => $order_id,
                                'amount' => $amount,
                                'after_tax' => $totalAmount,
                                'taxAmount' => $taxAmount,
                                'offer_amount' => 0,
                                'gst' => $taxPercentage,
                                'order_id' => $order_id,
                                'gateway_response' => $paymenresult,
                                'billingaddressdetails' => $billingaddressdetails
                            ]
                        ]);
                    }

                    // // dd($paymenresult);




                    // $redirec_url='https://astroera.in/cc_avance/ccavResponseHandler.php';
                    // $cancel_url='https://astroera.in/cc_avance/ccavResponseHandler.php';
                    // $payload = "merchant_id=".env('CCAVENUE_MERCHANT_ID')."&order_id=$order_id&amount=$totalAmount&currency=INR&redirect_url=$redirec_url&cancel_url=$cancel_url";
                    // $encryptedData = encryptGateway($payload, env('CCAVENUE_WORKINGKEY'));

                    // $response=[
                    //     'payload'=>$payload,
                    //     'encRequest' => $encryptedData,
                    //     'accessCode' => env('CCAVENUE_ACCESSCODE'),
                    //     'actionUrl' => 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction',
                    // ];
                    // return ApiResponse(200,true,'Please make payment',$response);

                }
            }
            return SimpleResponse(404, false, "Invalid Pooja or not found");
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }


    public function Webhookresponse(Request $request)
    {

        $dataintoDB = json_encode($request->all());

        $storeToDB = DB::select("INSERT INTO `demos` (`data`) VALUES ('$dataintoDB')");

        $order_id = $request->order_id;

        $status = $request->status;
        if (!empty($request->payment_id)) {

            $payment_id = $request->payment_id;
        } else {
            $payment_id = '';
        }
        $obj = Checkout::where('order_id', $order_id)->first();

        if ($status == 'Completed') {
            $obj->order_status = $status;
            $obj->save();
        }

        $objs = Checkout::where('order_id', $order_id)->first();
        $data = Payment::where('order_id', $order_id)->first();
        $date_year = ($data->created_at)->format('Y');
        $date_month = ($data->created_at)->format('m');
        if ($date_month < 4) {
            $financial_year = ($date_year - 1) . '-' . $date_year;
        } else {
            $financial_year = $date_year . '-' . ($date_year + 1);
        }

        $data1 = Payment::where('financial_year', $financial_year)->where('payment_status', 'Completed')->orderBy('invoice_no', 'DESC')->first();
        if ($status == 'Completed') {
            $obj1 = Payment::where('order_id', $order_id)->first();
            $obj1->user_id = $objs->user_id;
            $obj1->payment_id = $payment_id;
            $obj1->total_amount = $objs->amount + $objs->tax_amount;
            $obj1->currency = 'INR';
            $obj1->payment_status = $status;
            $obj1->post_meta = '100669';
            $obj1->gateway_name = 'ccavenue';
            $obj1->financial_year = $financial_year;
            $obj1->invoice_no = $data1->invoice_no + 1;
            $obj1->save();
            if ($obj1->save()) {
                return $obj1;
            }
        } else {
            return (["message" => "Payment failed Something went wrong"]);
        }
    }
}
