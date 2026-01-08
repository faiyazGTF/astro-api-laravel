<?php

namespace App\Http\Controllers\Astrologer;

use App\Http\Controllers\ChatAndCallController;
use App\Http\Controllers\Common\CommonController;
use App\Http\Controllers\FireBaseActionController;
use App\Http\Controllers\Controller;
use App\Models\CallChatRequest;
use App\Models\ConsultRemedies;
use App\Models\ConsultRemenies;
use App\Models\EnquiryModel;
use App\Models\NoticeBoard;
use App\Models\User\AppVersion;
use App\Models\User\AstroGallery;
use App\Models\User\AstroPriceRange;
use App\Models\User\FollowModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User\User;
use App\Models\User\UsersDetail;
use App\Models\User\WalletsModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Str;
use File;
use App\Models\AstroConferencing;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\returnSelf;

class AstrologerController extends CommonController
{
    //
    public static $lang = 'en';

    public function login(Request $request)
    {

        // $request->merge(['user_type' => 'ASTROLOGER']);

        $response = User::astrologin($request);
        return $response;
    }

    public function  logout(Request $request)
    {
        $response = User::logout($request);
        return $response;
    }
    public function resendOTP(Request $request)
    {
        $response = User::resendOTP($request);
        return $response;
    }
    public function OtpVerify(Request $request)
    {
        $response = User::VerifyOtp($request);
        return $response;
    }
    public function getProfile(Request $request, $userid)
    {

        $response = User::getProfile($request, $userid);
        return $response;
    }
    public function refreshToken(Request $request)
    {

        $response = User::refreshToken($request);
        return $response;
    }
    public function updateProfileImage(Request $request)
    {
        $response = User::updateProfileImage($request);
        return $response;
    }
    // pendig 
    public  function UpdateProfile(Request $request)
    {
        $response = User::UpdateAstroProfile($request);
        return $response;
    }
    public function FollowAstrologer(Request $request)
    {
        $response = User::FollowAstrologer($request);
        return $response;
    }
    public function myFollowing(Request $request, $userid)
    {

        $response = User::MyFollowing($request, $userid);

        return ApiResponse(200, true, "success", $response);
    }
    public function DeleteAccount(Request $request)
    {

        $response = User::DeleteAccount($request);
        return $response;
    }
    public function ChatHistory(Request $request, $cousultId)
    {
        $result = CallChatRequest::ChatHistory($request, $cousultId);
        return ApiResponse(200, true, 'success', $result);
    }



    public static function  customerSuppoert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'mobile' => 'required',
            'enq_type' => 'required',
            'description' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Please Fill Mandatory fields',
                'errors' => $validator->errors()
            ]);
        }
        $postfeedback = EnquiryModel::PostEnquiry($request->name, $request->email, $request->mobile, $request->enq_type, $request->description, 'enquiry', $status = 0);
        return $postfeedback;
    }


    public function getMinPriceRange()
    {
        $checkminprice = AstroPriceRange::first();
        if ($checkminprice) {
            return ApiResponse(200, true, 'Get Astro price range', $checkminprice);
        }
    }


    public function UpdatePrice(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'chat_price' => 'required',
                'call_price' => 'required',
                'video_call_price' => 'required',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $authid = $request->auth_user->id;
            $records = UsersDetail::where("user_id", $authid)->first();

            $businessValidator = Validator::make([], []); // Empty data and rules initially

            if ($request->call_price > $records->astro_call_charges) {
                $businessValidator->errors()->add('call_price', 'Call price cannot be higher than astrologer call charges');
            }
            if ($request->chat_price > $records->astro_chat_charges) {
                $businessValidator->errors()->add('chat_price', 'Chat price discount cannot be higher than astrologer chat charges');
            }
            if ($request->video_call_price > $records->astro_video_charges) {
                $businessValidator->errors()->add('video_call_price', 'Video call price discount cannot be higher than astrologer video charges');
            }

            if ($businessValidator->errors()->any()) {
                return errorResponse($businessValidator->errors());
            }


            if ($request->call_price <= $records->astro_call_charges) {
                $records->disc_call_charge = $request->call_price;
            }
            if ($request->chat_price <= $records->astro_chat_charges) {
                $records->disc_chat_charge = $request->chat_price;
            }
            if ($request->video_call_price <= $records->astro_video_charges) {
                $records->disc_video_charge = $request->video_call_price;
            }

            // $records->disc_call_charge = $request->call_price;
            // $records->disc_chat_charge = $request->chat_price;
            // $records->disc_video_charge = $request->video_call_price;
            $records->save();
            return ApiResponse(200, true, 'Price Updated Successfully', $records);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function OnlineUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:1,0',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $authid = $request->auth_user->id;
            $records = UsersDetail::where("user_id", $authid)->first();

            $records->availability = $request->status;

            $getuser = User::find($authid);

            // 🔔 Send notification ONLY when going online (status = 1)
            if ($request->status == 1) {
                $followers = DB::table('tbl_follow')->where('expert_id', $getuser->id)->pluck('user_id');

                if ($followers->isNotEmpty()) {
                    foreach ($followers as $followerId) {
                        $fcmToken = getFcmToken($followerId);

                        if (!empty($fcmToken)) {
                            $notificationarray = [
                                'title' => $getuser->name . ' is online',
                                'message' => 'Join before their waitlist grows!',
                                'image' => image_url($getuser->image, '/public/cms-images/user-images/'),
                                'type' => 'astroera://astro/' . $getuser->id . '/' . $getuser->name,

                                'senderid' => $getuser->id
                            ];

                            FireBaseActionController::PushNOtificationAuthdata($fcmToken, $notificationarray);
                        }
                    }
                }
            }

            $records->save();
            return SimpleResponse(200, true, 'availability Updated Successfuly');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function managePromotionalOfferStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:1,0',
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }
            $authid = $request->auth_user->id;
            $obj = UsersDetail::where("user_id", $authid)->first();
            $obj->is_promotional_accept = $request->status;
            $obj->save();
            return SimpleResponse(200, true, 'Promotion Details Successfuly');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }


    public function updateServiceStatus(Request $request)
    {

        try {
            $authid = $request->auth_user->id;
            $newFlags[] = $request->chat == "true" ? "chat" : "";
            $newFlags[] = $request->call == "true" ? "call" : "";
            $newFlags[] = $request->video == "true" ? "video" : "";
            $astrodata = UsersDetail::where("user_id", $authid)->first();
            $existingFlags = explode(",", $astrodata->flags);
            if (in_array("home", $existingFlags)) {
                $newFlags[] = "home";
            }
            if (in_array("webinar", $existingFlags)) {
                $newFlags[] = "webinar";
            }
            $flagCombined = implode(",", array_filter($newFlags));
            $astrodata->flags = $flagCombined;
            $astrodata->save();
            return ApiResponse(200, true, 'Service Update Successfully', $newFlags);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }


    public function getCallWaitList(Request $request)
    {
        try {
            $authid = $request->auth_user->id;

            $callWaitlist = CallChatRequest::leftjoin("users", "users.id", "=", "call_chat_request.user_id")
                ->leftjoin(
                    "mst_order_status",
                    "mst_order_status.order_status_id",
                    "=",
                    "call_chat_request.request_status"
                )
                ->select(
                    "call_chat_request.request_session_id",
                    "users.name as client_name",
                    "users.image as image",
                    "mst_order_status.name as status_name",
                    "call_chat_request.request_type"
                )
                ->where("call_chat_request.expert_id", $authid)

                // ->where('call_chat_request.request_type', $query)
                ->where("call_chat_request.request_status", 20)
                ->orderby("call_chat_request.id", "ASC")
                ->paginate(10)->map(function ($item) {

                    $item->image = image_url($item->image, '/public/cms-images/user-images/');

                    return $item;
                });


            // $call_count =  RequestCallChat   

            return ApiResponse(200, true, 'succcess', $callWaitlist);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function getConsultHistory(Request $request)
    {
        try {
            $type = $request->type;
            $authid = $request->auth_user->id;

            $result = CallChatRequest::join('users', 'users.id', '=', 'call_chat_request.expert_id')
                ->leftjoin('mst_order_status', 'mst_order_status.order_status_id', '=', 'call_chat_request.request_status')
                ->leftjoin('wallets', function ($join) {
                    $join->on('wallets.transaction_id', '=', 'call_chat_request.request_session_id')
                        ->whereColumn('wallets.user_id', 'call_chat_request.expert_id'); // Additional condition
                })

                ->select('call_chat_request.is_promotional', 'call_chat_request.created_at', 'call_chat_request.user_chat_charges as total_charges', 'call_chat_request.request_type as consult_type', 'call_chat_request.user_name as name', 'users.id as user_id', 'call_chat_request.request_session_id as consult_id', 'call_chat_request.total_duration', 'call_chat_request.astro_chat_charge', 'call_chat_request.astro_video_call_charge', 'call_chat_request.astro_call_chagre', 'wallets.amount as earn_amount', 'mst_order_status.name as status', 'wallets.id as wallet_id')
                ->when($type, function ($query, $type) {
                    $query->where('call_chat_request.request_type', $type);
                })
                ->where('expert_id', $authid)
                ->groupby('call_chat_request.id')
                ->orderBy('call_chat_request.id', 'DESC')

                ->paginate(10);



            return ApiResponse(200, true, 'success', $result);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function getChatData($cousultId)
    {
        try {
            $result = CallChatRequest::join('users', 'users.id', '=', 'call_chat_request.user_id')
                ->join('mst_order_status', 'mst_order_status.order_status_id', '=', 'call_chat_request.request_status')
                ->leftjoin('wallets', 'wallets.user_id', '=', 'call_chat_request.expert_id')
                ->select('call_chat_request.form_meta', 'call_chat_request.request_type as consult_type', 'users.name', 'users.id as user_id', 'call_chat_request.request_session_id as consult_id', 'call_chat_request.total_duration', 'call_chat_request.astro_chat_charge', 'call_chat_request.astro_video_call_charge', 'call_chat_request.astro_call_chagre', 'wallets.amount as earn_amount', 'mst_order_status.name as status')
                ->where('request_session_id', $cousultId)->first();
            if ($result) {
                $result->form_meta = unserialize($result->form_meta);
                $result->remidies = ChatAndCallController::GetRemedies($cousultId);
            }
            return ApiResponse(200, true, 'success', $result);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function getNoticeBoard(Request $request)
    {
        try {
            $result = NoticeBoard::where('usertype', 'ASTROLOGER')->get()->map(function ($item) {
                $item->image = image_url($item->image); // Adding base URL
                return $item;
            });


            return ApiResponse(200, true, 'success', $result);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function getFollower(Request $request)
    {
        try {
            $authid = $request->auth_user->id;


            $totalCount = FollowModel::where("tbl_follow.expert_id", $authid)->count(); // Get total count
            $data = FollowModel::join("users", "users.id", "=", "tbl_follow.user_id")
                ->join("users_details", "users.id", "=", "users_details.user_id")
                ->where("tbl_follow.expert_id", $authid)
                ->select("tbl_follow.*", "users.name", "users.image", "users_details.about_me_hn", "users_details.about_me_en")
                ->orderby("tbl_follow.id", "DESC")
                ->paginate(10);

            // Modify paginated data correctly
            $data->getCollection()->transform(function ($item) use ($request) {
                $item->image = image_url($item->image, '/public/cms-images/user-images/');
                $item->bio = $item->about_me_en;

                if ($request->has('lang') && $request->lang == 'hi') {
                    $item->bio = $item->about_me_hn;
                }

                return $item;
            });

            return ApiResponse(200, true, 'Success', ['records' => $data, 'total' => $totalCount]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function getGallery(Request $request)
    {
        try {
            $authid = $request->auth_user->id;
            $data = AstroGallery::where("user_id", $authid)
                ->orderby("id", "DESC")
                ->paginate(10)->map(function ($item) {
                    $item->full_image_url = image_url($item->image, '/public/cms-images/astro-gallery/');
                    return $item;
                });
            return ApiResponse(200, true, 'Success', $data);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function saveGallery(Request $request)
    {
        try {
            $authid = $request->auth_user->id;

            // Validate Image
            $validator = Validator::make($request->all(), [
                "image" => "required|mimes:jpg,jpeg,png|max:2048",
            ]);

            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $image = $request->file("image");
            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $fileExt = $image->extension();
            $imageName = rand() . "_" . Str::slug($originalName) . "." . $fileExt;



            $imagepath =  Storage::disk('s3')->putFileAs('public/cms-images/astro-gallery', $request->file("image"), $imageName);

            // Save Data
            $data = AstroGallery::create([
                "user_id" => $authid,
                "image_path" => '/public/cms-images/astro-gallery/',
                "image" => $imageName,
            ]);

            return ApiResponse(200, true, "Success", $data);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }
    public function deleteGallery(Request $request)
    {
        $gallery_ids = $request->gallery_ids;

        if (!is_array($gallery_ids)) {
            return ApiResponse(400, false, "Gallary id must be an array.");
        }

        try {
            $deleted = [];

            foreach ($gallery_ids as $id) {
                $obj = AstroGallery::where("id", $id)->first();

                if ($obj) {
                    // Delete image file if exists
                    $image_path = public_path("cms-images/astro-gallery/") . "/" . $obj->user_id . "/" . $obj->image;
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }

                    // Delete DB record
                    $obj->delete();
                    $deleted[] = $id;
                }
            }

            return ApiResponse(200, true, "Deleted successfully", ['deleted_ids' => $deleted]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }


    public function getWalletReport(Request $request)
    {
        try {
            $authid = $request->auth_user->id;
            $type = $request->type;


            $userDetails = UsersDetail::select('balance_amount')->where("user_id", $authid)->first();
            if ($userDetails) {
                $totalAmountPaid = WalletsModel::select(
                    DB::raw("SUM(amount) as totalAmount")
                )
                    ->where("transaction_type", "=", "debits")
                    ->where("product_type", "=", "payout")
                    ->where("user_id", $authid)
                    ->when($type, function ($query, $type) {
                        $query->where('product_type', $type);
                    })
                    ->first();

                $currentWeekRevenue = WalletsModel::select(DB::raw("SUM(amount) as totalAmount"))->where(function ($query) {
                    $query->where("transaction_type", "=", "credits")->orWhere("transaction_type", "=", "credit");
                })->whereBetween("created_at", [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ])->where("user_id", $authid)
                    ->when($type, function ($query, $type) {
                        $query->where('product_type', $type);
                    })
                    ->first();

                $lastWeekRevenue = WalletsModel::select(DB::raw("SUM(amount) as totalAmount"))->where(function ($query) {
                    $query->where("transaction_type", "=", "credits")->orWhere("transaction_type", "=", "credit");
                })->whereBetween("created_at", [
                    Carbon::now()->subWeek()->startOfWeek(),
                    Carbon::now()->subWeek()
                        ->endOfWeek(),
                ])->where("user_id", $authid)
                    ->when($type, function ($query, $type) {
                        $query->where('product_type', $type);
                    })
                    ->first();

                $totalEarning = WalletsModel::select(
                    DB::raw("SUM(amount) as totalAmount")
                )->where(function ($query) {
                    $query->where("transaction_type", "=", "credits")->orWhere("transaction_type", "=", "credit");
                })->where("user_id", $authid)

                    ->when($type, function ($query, $type) {
                        $query->where('product_type', $type);
                    })
                    ->first();


                $tdsDeposited = WalletsModel::select(
                    DB::raw("SUM(amount) as totalAmount")
                )->where("transaction_type", "=", "debits")->where("product_type", "=", "tds")->where("user_id", $authid)->first();

                // 1. Get TDS % from tds_total_amount table (assuming single row)
                $tdsRow = DB::table('tds_total_amount')->orderBy('id', 'desc')->first();
                $tdsPercentage = $tdsRow ? floatval($tdsRow->tds_cur) : 0;
                $totalEarningValue = $totalEarning ? floatval($totalEarning->totalAmount) : 0;

                $tdsDepositedAmount = ($totalEarningValue * $tdsPercentage) / 100;

                $pendingTds = ($userDetails->balance_amount * $tdsPercentage) / 100;

                $walletbalance = $userDetails->balance_amount - $pendingTds;


                $result["walletbalnce"] = number_format($walletbalance, 2);
                $result["pendingTds"] = number_format($pendingTds, 2);
                $result["tdsPercentage"] = number_format($tdsPercentage, 1);
                $result["totalAmountPaid"] = number_format(
                    $totalAmountPaid->totalAmount,
                    2
                );
                $result["currentWeekRevenue"] = number_format(
                    $currentWeekRevenue->totalAmount,
                    2
                );
                $result["lastWeekRevenue"] = number_format(
                    $lastWeekRevenue->totalAmount,
                    2
                );
                $result["totalEarning"] = number_format(
                    $totalEarning->totalAmount,
                    2
                );


                // $result["tdsDeposited"] = number_format(
                //     $tdsDeposited->totalAmount,
                //     2
                // );

                $result["tdsDeposited"] = number_format($tdsDepositedAmount, 2);

                return ApiResponse(200, true, 'success', $result);
            }
            return SimpleResponse(404, false, 'Failed to fetch data');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function getWalletHistory(Request $request)
    {
        try {
            $authid = $request->auth_user->id;
            $type = $request->type;
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $search = "";
            if (!empty($request->search)) {
                $search = $request->search;
            }
            $userDetails = UsersDetail::select('balance_amount')->where("user_id", $authid)->first();
            if ($userDetails) {
                $sort = !empty($request->sort) ? $request->sort : 'DESC';

                $key =  !empty($request->sort) ? 'amount' : 'created_at';

                $result = WalletsModel::select(
                    "wallets.id",
                    "wallets.user_id",
                    "wallets.transaction_id",
                    "wallets.transaction_type",
                    "wallets.amount",
                    "wallets.product_type",
                    "wallets.transaction_by",
                    "wallets.remarks",
                    "wallets.balance_amount",
                    "wallets.payment_id",
                    "wallets.parent_id",
                    "wallets.wallets_meta",
                    "wallets.astro_pay_status",
                    'gifts.title as gitname',
                    'gifts.image as gift_image',
                    "wallets.created_at",
                    "wallets.updated_at"
                )
                    ->leftJoin('gifts', 'gifts.id', '=', 'wallets.transaction_id')
                    ->when($search, function ($query) use ($search) {
                        // Basic search implementation if you don't want to create a scope
                        return $query->where('transaction_id', 'like', "%{$search}%");
                    })
                    ->where("user_id", $authid)
                    ->when($type, function ($query, $type) {
                        $query->where('product_type', $type);
                    })
                    ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                        $query->whereBetween('created_at', [$from_date, $to_date]);
                    })

                    // ->groupBy("transaction_id" )

                    ->orderby($key, $sort)

                    ->paginate(10);

                $result->getCollection()->transform(function ($item) {
                    if (!empty($item->gift_image)) {
                        $item->gift_image = image_url($item->gift_image, '/public/cms-images/gift/');
                    }
                    return $item;
                });

                return ApiResponse(200, true, 'success', $result);
            }
            return SimpleResponse(404, false, 'Failed to fetch data');
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }



    public function acceptSession(Request $request, $sessiondid)
    {
        try {


            $currentTime = date('Y-m-d H:i:s', time());
            $currentTime2 = date('Y-m-d H:i:s', time());

            if (!empty($request->query('StartTime')) && !empty($request->query('EventTime'))) {
                $rawTime = $request->query('StartTime');
                $currentTime = Carbon::parse($rawTime)->format('Y-m-d H:i:s');
                DB::select("INSERT INTO `log_data` (`value`) VALUES ('$currentTime')");

                $rawTime2 = $request->query('EventTime');
                $currentTime2 = Carbon::parse($rawTime2)->format('Y-m-d H:i:s');
            }


            $call_details = CallChatRequest::where('request_session_id', $sessiondid)->where('request_status', 1)->first();
            $updateExpertStatus = UsersDetail::where('user_id', @$call_details->expert_id)->first();

            if (!$call_details || !$updateExpertStatus) {
                return SimpleResponse(401, false, "Unauthorized access");
            }



            $call_details->request_status = 2; /// start
            $call_details->astro_start_time = $currentTime;
            $updateExpertStatus->availability = 2; // set expert bust
            $updateExpertStatus->save();
            $call_details->save();

            $checkUser = User::select('users.user_type', 'users_details.balance_amount', 'users.id', 'users.image')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $call_details->user_id)->first();
            $user_image = !empty(!$checkUser->image) ? image_url($checkUser->image, '/public/cms-images/user-images/') : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
            $call_details->max_end_time = getmaxExpectedTime($sessiondid);

            if (!empty($request->query('StartTime')) && !empty($request->query('EventTime'))) {
                $diffInSeconds = Carbon::parse($currentTime2)->diffInSeconds(Carbon::parse($currentTime));

                $maxTime = $call_details->max_end_time['data']['max_time'] ?? null;

                if ($maxTime) {
                    $end_time = Carbon::parse($maxTime)
                        ->addSeconds($diffInSeconds)
                        ->format('Y-m-d H:i:s');
                } else {
                    $end_time = Carbon::parse($currentTime2)
                        ->addSeconds($diffInSeconds)
                        ->format('Y-m-d H:i:s');
                }
            } else {
                $end_time = $call_details->max_end_time['data']['max_time'] ?? '';
            }

            // Initialize Socket Timer
            try {
                $response = Http::post(
                    env('SOCKET_SERVER_URL', 'http://localhost:65282') . '/update-server-timer',
                    [
                        'room'    => $sessiondid,
                        'endTime' => $end_time
                    ]
                );

                // dd($response);
                // Call only if API response is successful (200–299)
                if ($response->successful()) {
                    FireBaseActionController::AstrologerConsultUpdate(
                        $call_details->expert_id,
                        $call_details->user_id,
                        $call_details->user_name,
                        $user_image,
                        $checkUser->balance_amount,
                        $call_details->request_type,
                        $sessiondid,
                        'active',
                        (!empty($currentTime2) ? $currentTime2 : $currentTime),
                        $end_time
                    );
                } else {
                    \Log::error('Socket API failed', [
                        'status' => $response->status(),
                        'body'   => $response->body()
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error("Socket Timer Init Failed: " . $e->getMessage());
            }



            $call_details->form_meta = unserialize($call_details->form_meta);

            $userdata = User::where('id', $call_details->user_id)->select('image')->first();

            $call_details->image = image_url($userdata->image, '/public/cms-images/user-images/');
            return ApiResponse(200, true, 'Session Accept', $call_details);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public function conferencing(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [

                // 'room_id'     => 'required',
                'is_available' => 'required|integer|in:0,1'
            ]);
            if ($validator->fails()) {
                return errorResponse($validator->errors());
            }

            $getVideoRoomId = getSDKRoomid();
            if ($getVideoRoomId) {
                $roomid = $getVideoRoomId['meet_id'];
                $token = $getVideoRoomId['token'];
            } else {
                return SimpleResponse(201, false, 'Something went wrong!');
            }

            $authid = $request->auth_user->id;
            $records = AstroConferencing::updateOrCreate(
                ['astro_id' => $authid],
                [
                    'roomid'     => $roomid,
                    'token' => $token,
                    'is_available' => $request->is_available
                ]
            );

            if ($request->is_available == 1) {
                $getuser = User::find($authid);

                $followers = DB::table('tbl_follow')->where('expert_id', $authid)->pluck('user_id');

                if ($followers->isNotEmpty()) {
                    foreach ($followers as $followerId) {
                        $fcmToken = getFcmToken($followerId);

                        if (!empty($fcmToken)) {
                            $notificationarray = [
                                'title' => $getuser->name . ' is now live!',
                                'message' => 'Join their live video session now',
                                'image' => image_url($getuser->image, '/public/cms-images/user-images/'),
                                'type' => 'video_session',
                                'senderid' => $getuser->id
                            ];

                            FireBaseActionController::PushNOtificationAuthdata($fcmToken, $notificationarray);
                        }
                    }
                }
            }

            return ApiResponse(200, true, 'success', $records);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
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
        $authid = $request->auth_user->id;
        $checkbusysession = CallChatRequest::where('expert_id', $authid)
            ->where('request_status', 2)
            ->exists();
        if ($checkbusysession) {
            return ApiResponse(403, false, 'Astrologer is already busy');
        }
        $checksession = CallChatRequest::where('request_session_id', $request->session_id)
            ->where('request_status', 20)
            ->first();
        if ($checksession) {
            $checkUser = User::select('users.user_type', 'users_details.balance_amount', 'users.id', 'users.image')->join('users_details', 'users_details.user_id', '=', 'users.id')->where('users.id', $checksession->user_id)->first();
            $user_image = !empty($checkUser->image)
                ? image_url($checkUser->image, '/public/cms-images/user-images/')
                : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";

            FireBaseActionController::AstrologerConsultUpdate($checksession->expert_id, $checksession->user_id, $checksession->user_name, $user_image, $checkUser->balance_amount, $checksession->request_type, $checksession->request_session_id, 'join_waitlist_initiate', '');

            // 🔔 Send notification to astrologer
            $getuser = User::find($checksession->expert_id);
            $getfcmtoken = getFcmToken($checksession->user_id);

            $type = 'join-waitlist';

            FireBaseActionController::PushNOtification(
                $getfcmtoken,
                'Waitlist Request Sent ' . $getuser->name,
                'You have requested to join the waitlist',
                $user_image,
                $type
            );

            //    FireBaseActionController::PushNOtificationAuthdata($getfcmtoken, $notificationarray);

            $checksession->form_meta = unserialize($checksession->form_meta);
            $checksession->max_end_time = getmaxExpectedTime($request->session_id);
            $checksession->image = $user_image;

            return ApiResponse(200, true, 'Success', $checksession);
        }
        return ApiResponse(404, false, 'Not found');
    }


    private function str_replace_array($search, array $replace, $subject)
    {
        foreach ($replace as $value) {
            $subject = preg_replace('/' . preg_quote($search, '/') . '/', is_numeric($value) ? $value : "'$value'", $subject, 1);
        }
        return $subject;
    }


    public function getMyTotalServiceDuration(Request $request)
    {
        try {
            $authid = $request->auth_user->id;

            $services = WalletsModel::join('call_chat_request', 'call_chat_request.request_session_id', '=', 'wallets.transaction_id')
                ->select(
                    'wallets.product_type',
                    DB::raw("SUM(wallets.amount) as totalAmount"),
                    DB::raw("SUM(call_chat_request.total_duration) as total_duration")
                )
                ->where(function ($query) {
                    $query->where("wallets.transaction_type", "=", "credits")
                        ->orWhere("wallets.transaction_type", "=", "credit");
                })
                ->where("wallets.user_id", $authid)
                ->whereIn('wallets.product_type', ['chat', 'calling', 'video'])
                ->groupBy('wallets.product_type')
                ->get()
                ->keyBy('product_type');

            $data = [
                'chat'    => $services->get('chat') ?? ['totalAmount' => 0, 'total_duration' => 0],
                'calling' => $services->get('calling') ?? ['totalAmount' => 0, 'total_duration' => 0],
                'video'   => $services->get('video') ?? ['totalAmount' => 0, 'total_duration' => 0],
            ];

            return ApiResponse(200, true, 'success', $data);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }




    public function AppVersion()
    {
        $response = AppVersion::where('type', 'user')->get();

        return ApiResponse(200, true, "success", $response);
    }

    public function replyToReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'review_id'     => 'required|numeric',
            'comment_reply' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ]);
        }

        $expertId = auth()->id();

        $review = DB::table('review')
            ->where('id', $request->review_id)
            ->where('to_experts', $expertId)
            ->first();

        if (!$review) {
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'Review not found or does not belong to this expert.'
            ]);
        }

        if (!empty($review->comment_reply)) {
            return response()->json([
                'statusCode' => 409,
                'status' => false,
                'message' => 'Reply already submitted for this review.'
            ]);
        }

        $updated = DB::table('review')
            ->where('id', $request->review_id)
            ->update([
                'comment_reply' => $request->comment_reply,
                'updated_at' => now()
            ]);

        if ($updated) {

            // 🔔 Send notification to astrologer
            $getuser = User::find($expertId);
            $getfcmtoken = getFcmToken($review->user_id);

            $notificationarray = [
                'title' => 'Reply Review from ' . $getuser->name,
                'message' => $request->comment_reply,
                'image' => image_url($getuser->image, '/public/cms-images/user-images/'),
                'type' => 'review',
                'senderid' => $getuser->id,
                'url' => 'astroera://review?astroId=' . $expertId
            ];

            FireBaseActionController::PushNOtificationAuthdata($getfcmtoken, $notificationarray);

            return response()->json([
                'statusCode' => 200,
                'status' => true,
                'message' => 'Reply submitted successfully.'
            ]);
        }

        return response()->json([
            'statusCode' => 500,
            'status' => false,
            'message' => 'Something went wrong while submitting reply.'
        ]);
    }

    public function getRatingAndReview(Request $request)
    {
        $expertId = $request->expert_id;

        if (!$expertId) {
            return response()->json([
                'statusCode' => 400,
                'status'     => false,
                'message'    => 'expert_id is required.'
            ]);
        }

        $reviews = DB::table('review')
            ->where('to_experts', $expertId)
            ->orderByDesc('id')
            ->get();

        if ($reviews->isEmpty()) {
            return response()->json([
                'statusCode' => 404,
                'status'     => false,
                'message'    => 'No reviews found for this expert.'
            ]);
        }

        $response = [];

        foreach ($reviews as $review) {
            $user = DB::table('users')->where('id', $review->user_id)->first();

            $userImage = !empty($user?->image)
                ? image_url($user->image, '/public/cms-images/user-images/')
                : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";

            $response[] = [
                'user_name'     => $user?->name ?? '',
                'user_image'    => $userImage,
                'rating'        => $review->rating,
                'id'            => $review->id,
                'consult_id'    => $review->consult_id,
                'anonymous'     => $review->is_anonymous ?? null,
                'comment'       => $review->comments ?? null,
                'comment_reply' => $review->comment_reply ?? null,
            ];
        }

        return response()->json([
            'statusCode' => 200,
            'status'     => true,
            'message'    => 'Success',
            'data'       => $response
        ]);
    }
}
