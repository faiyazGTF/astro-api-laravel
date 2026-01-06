<?php

namespace App\Models\User;

use App\Http\Controllers\FireBaseActionController;
use App\Models\Commons\Cities;
use App\Models\Commons\Countries;
use App\Models\Commons\State;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User\UsersDetail;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    public static $lang = 'en';

    protected $appends = ['full_image_url']; // Append to API responses
    public function getFullImageUrlAttribute()
    {
        return image_url($this->image, $this->image_path);
    }


    public function getJWTIdentifier()
    {
        return $this->getKey(); // Usually the primary key of the user
    }
    public function following()
    {
        return $this->belongsToMany(User::class, 'tbl_follow', 'expert_id');
    }

    public function isFollowing($user_id)
    {
        return $this->following()->where('user_id', $user_id)->exists();
    }

    public function Gallery()
    {
        return $this->hasMany(AstroGallery::class, 'user_id');
    }

    public function ReviewBy()
    {
        return $this->hasMany(ReviewsModel::class, 'to_experts')->with('User');
    }




    public function getJWTCustomClaims()
    {
        return [];
    }
    public function userDetail()
    {
        return $this->hasOne(UsersDetail::class, 'user_id')->select(
            'profile_name_hn',
            'about_me_en',
            'about_me_hn',
            'specialisation',
            'languages',
            'experience',
            'is_login',
            'availability',
            'flags',
            'rating',
            'slug',
            'astro_call_charges',
            'astro_chat_charges',
            'disc_call_charge',
            'disc_chat_charge',
            'astro_video_charges',
            'disc_video_charge',
            'image_path',
            'profile_image',
            'is_promotional_accept',
            'label',

            'city',
            'country',
            'state',
            'user_id'
        );
    }



    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'email_verified_at',
        'email_verification_token',
        'image',
        'user_type',
        'status',
        'tokens'
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function CreateGuestUser($countycode, $mobile, $otp, $user_type, $status = 0)
    {
        try {
            $userObjects = new User();
            $userObjects->name         = 'Guest User';
            $userObjects->email        = 'new_' . time() . '@xyz.in';
            $userObjects->password     = Hash::make('987654321');
            $userObjects->country_code = $countycode;
            $userObjects->mobile       = $mobile;
            $userObjects->otp          = $otp;
            $userObjects->user_type    = $user_type;
            $userObjects->status       = $status;
            $userObjects->image        = 'default/default-user-image.png';
            if ($userObjects->save()) {
                $userprofile                = new UsersDetail();
                $userprofile->user_id       = $userObjects->id;
                $userprofile->mobile2       = $mobile;
                $userprofile->profile_name_en       = 'Guest User';
                $userprofile->profile_image = 'default/astro-expert-banner.jpg';
                $userprofile->image_path    = '/public/cms-images/user-images/';
                $userprofile->save();
                sendOtp($otp, $countycode, $mobile);
                return true;
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
    public static function  login($request)
    {

        $validator = Validator::make($request->all(), [
            'country_code' => 'required',
            'mobile'       => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Please Fill Mandatory fields',
                'errors' => $validator->errors()
            ]);
        }

        $otp = generateOTP();
        $checkuser = User::where('mobile', $request->mobile)
            ->orderBy('id', 'desc')
            ->first();

        $mobile = $request->mobile;

        $countycode = !empty($request->country_code) ? $request->country_code : "91";
        if ($checkuser) {

            if ($checkuser->is_deleted == 1) {
                $status = $request->user_type == "ASTROLOGER" ? 0 : 1;
                $result = self::CreateGuestUser($countycode, $mobile, $otp, $request->user_type, $status);

                if ($result == true) {
                    return ApiResponse(200, true, "One time OTP is $otp sent on $mobile");
                }
                return InternalError($result);
            }

            if ($checkuser->id == 9) {
                $otp = 1234;
            }

            if ($checkuser->user_type == 'ASTROLOGER') {
                return response()->json([
                    'statusCode' => 403,
                    'status' => false,
                    'message' => 'Mobile no already registered.',
                ]);
            }
            $mobilesms = sendOtp($otp, $countycode, $mobile);


            $checkuser->otp = $otp;
            $checkuser->save();

            return ApiResponse(200, true, " One time OTP is $otp sent On .$mobile", true);
        } else {
            $status = $request->user_type == "ASTROLOGER" ? 0 : 1;
            $result = self::CreateGuestUser($countycode, $mobile, $otp, $request->user_type, $status);
            if ($result == true) {
                return ApiResponse(200, true, " One time is $otp OTP sent On .$mobile");
            }
            return InternalError($result);
        }
    }

    public static function  astrologin($request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {

            return errorResponse($validator->errors());
        }
        $checkuser = User::where('email', $request->email)->where('user_type', 'ASTROLOGER')->first();

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password]) && $checkuser) {
            $token = Auth::guard('api')->login($checkuser);



            $user                 = $checkuser;
            $success['token']     = $token;
            $success['id']        = $user->id;
            $success['name']      = $user->name;
            $success['user_type'] = $user->user_type;
            $success['is_signup_complete'] = $checkuser->is_signup_complete;

            $success['success'] = true;

            $obj = user::where(['id' => $user->id])->first();
            $obj->firebase_tokens = $request->firebasetoken;
            $obj->save();

            if ($obj->is_deleted == 1) {
                Auth::logout();

                return ApiResponse(403, true, 'Your Account is Deleted. Contact Admin.');
            } else {

                $getuser = User::find($user->id);

                // Get all followers of this expert
                $followers = DB::table('tbl_follow')->where('expert_id', $getuser->id)->pluck('user_id');

                if ($followers->isNotEmpty()) {
                    foreach ($followers as $followerId) {
                        $fcmToken = getFcmToken($followerId); // You already have this helper

                        if (!empty($fcmToken)) {
                            $notificationarray = [
                                'title' =>  $getuser->name . ' is online: ',
                                'message' => 'Join before their waitlist grows',

                                'image' => image_url($getuser->image, '/public/cms-images/user-images/'),
                                'type' => 'online_status',
                                'senderid' => $getuser->id
                            ];

                            FireBaseActionController::PushNOtificationAuthdata($fcmToken, $notificationarray);
                        }
                    }
                }


                return ApiResponse(200, true, 'User login successfully.', $success);
            }
        } else {
            return ([
                "success" => false,
                "message" => "Invalid email or password",
                "data" => ["message" => "Unauthorised access", "success" => false,]
            ]);

            // $this->sendResponse('Unauthorised.', ['data' => 'Unauthorised']);
        }
    }
    public static function  logout($request)
    {





        $token = JWTAuth::getToken();

        $manager = JWTAuth::manager()->setBlacklistEnabled(true);
        JWTAuth::invalidate($token);

        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'Logout Successfully',
        ]);
    }

    public  static function resendOTP($request)
    {
        $validator = Validator::make($request->all(), [
            'country_code' => 'required',
            'mobile'       => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Please Fill Mandatory fields',
                'errors' => $validator->errors()
            ]);
        }
        $checkuser = User::where('mobile', $request->mobile)
            ->orderBy('id', 'desc')
            ->first();

        $mobile = $request->mobile;
        $country_code = $request->country_code;
        if (!$checkuser) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'something went wrong',
            ]);
        }
        $otp = generateOTP();
        $checkuser->otp = $otp;
        if ($checkuser->save()) {
            sendOtp($otp, $country_code, $mobile);

            return response()->json([
                'statusCode' => 200,
                'status' => true,
                'message' => ' One time OTP sent On ' . $mobile,
            ]);
        }
        return response()->json([
            'statusCode' => 403,
            'status' => false,
            'message' => 'something went wrong',
        ]);
    }
    public static function VerifyOtp($request)
    {
        $validator = Validator::make($request->all(), [
            'mobile'       => 'required|numeric',
            'otp'       => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Please Fill Mandatory fields',
                'errors' => $validator->errors()
            ]);
        }
        $mobile = $request->mobile;
        $otp = $request->otp;
        $checkuser = User::where('mobile', $request->mobile)
            ->orderBy('id', 'desc')
            ->first();

        if (!$checkuser) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'something went wrong',
            ]);
        }
        if ($checkuser->otp != $otp) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Invalid Otp',
            ]);
        }

        // Auth::login($checkuser);
        $expiry = Carbon::now('Asia/Kolkata')->addMinutes(config('jwt.ttl'));
        $expiryFormatted = $expiry->toDateTimeString();
        $token = Auth::guard('api')->login($checkuser);
        if ($checktoken = DB::table('oauth_refresh_tokens')->where('id', $checkuser->id)->first()) {
            DB::table('oauth_refresh_tokens')
                ->where('id', $checkuser->id)
                ->update([
                    'access_token_id' => $token,
                    'revoked' => $checktoken->revoked + 1,
                    'expires_at' => $expiryFormatted,
                ]);
        } else {
            DB::table('oauth_refresh_tokens')->insert([
                'id' => $checkuser->id,
                'access_token_id' => $token,
                'revoked' => 1,
                'expires_at' => $expiryFormatted,
            ]);
        }





        $success['token']     = [
            'AutorizationType' => 'Bearer',
            'token' => $token,
            'expires_at' => $expiry
        ];
        $success['id']        = $checkuser->id;
        $success['name']      = $checkuser->name;
        $success['user_type'] = $checkuser->user_type;
        $success['is_signup_complete'] = $checkuser->is_signup_complete;
        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'data' => $success
        ]);
    }

    public static function getProfile($request, $userid)
    {


        $user_mobile = DB::table('users')->where('id', $userid)->value('mobile');

        // Default is_new_user_active = 1
        $is_new_user_active = 1;

        // Check if user has done any non-promotional chat
        $user_last_call = DB::table('call_chat_request')
            ->where('user_id', $userid)
            //  ->where('is_promotional', '!=', 1)
            ->where('request_type', 'Chat')
            ->whereIn('request_status', [2, 5])
            ->exists();

        if ($user_last_call) {
            $is_new_user_active = 0;
        }

        // Check if any deleted user exists with same mobile
        $existingDeletedUsers = DB::table('users')
            ->where('mobile', $user_mobile)
            ->where('is_deleted', 1)
            ->exists();

        if ($existingDeletedUsers) {
            $is_new_user_active = 0;
        }



        $userdata = User::leftjoin('users_details', 'user_id', '=', 'users.id')
            ->leftjoin('mst_countries', 'mst_countries.id', '=', 'users_details.country')
            ->leftjoin('mst_states as st1', 'st1.id', '=', 'users_details.state')
            ->leftjoin('mst_cities as ct1', 'ct1.id', '=', 'users_details.city')
            ->leftjoin('mst_states as st2', 'st2.id', '=', 'users_details.cur_state')
            ->leftjoin('mst_cities as ct2', 'ct2.id', '=', 'users_details.cur_city')
            ->leftjoin('mst_languages', 'mst_languages.id', '=', 'users_details.languages')
            ->select(
                'users.id',
                'users.name',
                'users.mobile',
                'users.email',
                'users.user_type',
                'users.created_at',
                'users.image',
                'users.life_time_rate',
                'users_details.*',
                'mst_countries.country_name',
                'st1.state_name as state_name',
                'ct1.city_name as city_name',
                'st2.state_name as cur_state_name',
                'ct2.city_name as cur_city_name'
            )
            ->where('users.id', $userid)
            ->first();

        if ($userdata) {
            if (!empty($request->lang) && $request->lang == 'hi' && !empty($userdata->profile_name_hn)) {
                $userdata->name = $userdata->profile_name_hn;
            }

            $userdata->is_new_user_active = $is_new_user_active;

            return response()->json([
                'statusCode' => 200,
                'status' => true,
                'message' => 'success',
                'data' => $userdata
            ]);
        }

        return response()->json([
            'statusCode' => 404,
            'status' => false,
            'message' => 'Not found',
        ]);
    }



    public static function refreshToken()
    {
        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'authorisation' => [
                'token' => Auth::guard('api')->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
    public static function updateProfileImage($request)
    {
        $authid = $request->auth_user->id;
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120' // aprox. 5 MB file.
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Please Fill Mandatory fields',
                'errors' => $validator->errors()
            ]);
        }
        $checkuser = User::where('id', $authid)->first();
        $imageName = time() . 'time-' . $checkuser->id . '-' . Str::slug($checkuser->name) . '.' . $request->profile_image->extension();
        $path = 'public/cms-images/user-images/' . $imageName;

        try {
            if (Storage::disk('s3')->exists($path)) {
                $deletrsukt =  Storage::disk('s3')->delete($path);
            }
        } catch (\Exception $e) {


            return response()->json([
                'statusCode' => 500,
                'status' => false,
                'message' => 'S3 bucket Error: ' . $e->getMessage(),
            ]);
        }

        $imagepath =  Storage::disk('s3')->putFileAs('public/cms-images/user-images', $request->file('profile_image'), $imageName);

        $checkuser->image = $imageName;

        // new code if image is wrong
        // $userDetailsDataUpdate = UsersDetail::where('user_id',$authid);
        // $userDetailsDataUpdate->update([
        //     'image_path' => '/public/cms-images/user-images/',
        //     'profile_image' => $imagepath,
        // ]);

        $imagepath = image_url('/' . $imagepath);
        if ($checkuser->save()) {
            return response()->json([
                'statusCode' => 200,
                'status' => true,
                'message' => 'Profile Image Updated successfully',
                'data' => $imagepath
            ]);
        }
        return response()->json([
            'statusCode' => 403,
            'status' => false,
            'message' => 'something went wrong',
        ]);
    }

    public static function defaultProfile($request)
    {
        $authid = $request->auth_user->id;

        $user = User::find($authid);

        if (!$user) {
            return response()->json([
                'statusCode' => 404,
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        // Set default image path
        $user->image = 'default/default-user-image.png';

        if ($user->save()) {
            $imagePath = asset('public/cms-images/user-images/default/default-user-image.png');
            return response()->json([
                'statusCode' => 200,
                'status' => true,
                'message' => 'Profile image set to default successfully',
                'data' => $imagePath
            ]);
        }

        return response()->json([
            'statusCode' => 500,
            'status' => false,
            'message' => 'Something went wrong'
        ]);
    }

    public static function UpdateProfile($request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'country'   => 'required|string',

                'state'     => 'required|string',
                'city'      => 'required|string',
                'birth_place'   => 'required|string',
                'lat'   => 'required',
                'long'   => 'required',

                'birth_time' => 'required|date_format:H:i',
                'dob' => 'required|date|date_format:Y-m-d',
                'gender'   => 'required',


            ]);
            if ($validator->fails()) {
                return response()->json([
                    'statusCode' => 403,
                    'status' => false,
                    'message' => 'Please Fill Mandatory fields',
                    'errors' => $validator->errors()
                ]);
            }
            $country = Countries::firstOrCreate(
                ['country_name' => $request->country], // Check if the country_name exists
                [
                    'sortname' => 0,
                    'phonecode' => 0,
                    'active_status' => 0,
                    'cont_digits' => 0
                ],
            );
            $authid = $request->auth_user->id;
            $request->dob = $request->dob . ' ' . $request->birth_time;

            $country = @$country->id;
            $ct = $request->city;
            $st = $request->state;

            $state = State::firstOrCreate(
                ['state_name' => $st], // Check if the country_name exists
                [
                    'country_id' => $country,
                    'state_name' => $request->state,
                ],
            );

            $city = Cities::firstOrCreate(
                ['city_name' => $ct], // Check if the country_name exists
                [
                    'city_name' => $ct,
                    'state_id' => $state->id,
                ],
            );

            $city = $city->id;
            $sity = $state->id;
            $checkuser = User::where('id', $authid)->first();
            if (!$checkuser) {
                return response()->json([
                    'statusCode' => 403,
                    'status' => false,
                    'message' => 'something went wrong',
                ]);
            }
            if (!empty($request->email)) {

                $checkdublicateemail = User::where('id', '<>', $authid)->where('email', $request->email)->first();
                if ($checkdublicateemail) {
                    return response()->json([
                        'statusCode' => 403,
                        'status' => false,
                        'message' => $request->email . 'Email already exists',
                    ]);
                }
            }
            $userschame = User::find($authid);

            $userschame->email = $checkuser->email;

            $detailsObj = UsersDetail::where('user_id', $authid)->first();
            $checkuser->name = $request->full_name ? $request->full_name : $checkuser->name;
            $checkuser->is_signup_complete = 1;

            // $checkuser->email = $request->email ? $request->email : $checkuser->email;
            $address_details = [];
            if (!empty($detailsObj->address_details)) {
                $address_details = json_decode($detailsObj->address_details, true);
            }
            $address_details['lat'] = $request->lat;
            $address_details['lng'] = $request->long;
            $address_details['time_of_birth'] = $request->birth_time;
            $detailsObj->country = $country;
            $detailsObj->city = $city;
            $detailsObj->profile_name_en = $request->full_name;
            $detailsObj->profile_name_hn = $request->full_name;

            if (!empty($request->email)) {
                $checkuser->email = $request->email;
            }

            if (!empty($request->address)) {
                $detailsObj->address = $request->address;
            }

            $detailsObj->state = $sity;
            $detailsObj->address_details = json_encode($address_details);
            $detailsObj->zip_code = !$request->zip_code ? $request->zip_code : "";
            $detailsObj->birth_place = $request->birth_place;
            $detailsObj->gender = $request->gender;
            $detailsObj->dob = $request->dob;

            $city2 = null;
            $sity2 = null;

            $st2 = $request->cur_state;
            $ct2 = $request->cur_city;

            if (!empty($st2)) {
                $state2 = State::where('state_name', $st2)->first();

                if ($state2) {
                    $sity2 = $state2->id;

                    if (!empty($ct2)) {
                        $city2 = Cities::where('city_name', $ct2)
                            ->where('state_id', $sity2)
                            ->first();

                        if ($city2) {
                            $city2 = $city2->id;
                        }
                    }
                }
            }

            $detailsObj->cur_state = $sity2;
            $detailsObj->cur_city = $city2;


            if (!empty($request->marital_status)) {
                $detailsObj->marital_status = $request->marital_status;
            }

            $detailsObj->is_updated = 1;
            if ($checkuser->save() && $detailsObj->save()) {
                return response()->json([
                    'statusCode' => 200,
                    'status' => true,
                    'message' => 'success',
                    'data' => $detailsObj,
                    'uudata' => $checkuser
                ]);
            }
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'failed',
            ]);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    // pending 
    public static function UpdateAstroProfile($request)
    {
        $validator = Validator::make($request->all(), [

            'full_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'language' => 'required|array|min:1', // Ensure it's an array and not empty
            'wellness_experiece'   => 'required',
            'about_astro'   => 'required',
            'experience' => 'required'

        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        $authid = $request->auth_user->id;

        $checkuser = User::where('id', $authid)->first();
        if (!$checkuser) {
            return SimpleResponse(403, false, "Unauthorized access");
        }

        $detailsObj = UsersDetail::where('user_id', $authid)->first();
        $checkuser->name = $request->full_name ? $request->full_name : $checkuser->name;
        $detailsObj->profile_name_en = $request->full_name ? $request->full_name : $checkuser->name;
        $detailsObj->about_me_en = $request->about_astro;

        if ($request->lang == 'hi') {
            $detailsObj->profile_name_hn = $request->full_name ? $request->full_name : $checkuser->name;
            $detailsObj->about_me_hn = $request->about_astro;
        }
        // $detailsObj->birth_place = $request->birth_place;
        $detailsObj->languages = implode(',', $request->language);
        $detailsObj->wellness_experiece = $request->wellness_experiece;
        $detailsObj->dob = $request->dob;
        $detailsObj->is_updated = 1;
        $detailsObj->experience = $request->experience;
        if ($checkuser->save() && $detailsObj->save()) {
            return SimpleResponse(200, true, 'Profile Updated Successfully');
        }
        return SimpleResponse(403, false, 'Failed to update Profile');
    }

    public static function FollowAstrologer($request)
    {


        $validator = Validator::make($request->all(), [
            'expert_id' => 'required|exists:users,id',
            'user_id' => 'required|exists:users,id',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Please Fill Mandatory fields',
                'errors' => $validator->errors()
            ]);
        }
        $checkalready = FollowModel::where('expert_id', $request->expert_id)
            ->where('user_id', $request->user_id)->first();

        if (!$checkalready) {
            $obj = new FollowModel();
            $obj->expert_id = $request->expert_id;
            $obj->user_id = $request->user_id;
            $obj->save();

            $getdata = User::find($request->user_id);
            $getuserdata = getFcmToken($request->expert_id);
            if ($getuserdata) {
                $image = "";
                if ($getdata->full_image_url) {
                    $image = $getdata->full_image_url;
                }
                $us_image = DB::table('users')
                    ->where('id', $request->user_id)
                    ->select('users.image as user_image')
                    ->first();
                if ($us_image) {
                    $us_imageuy = !empty($us_image->user_image)
                        ? "https://astroera.in/public/cms-images/user-images/" . $us_image->user_image
                        : "https://wallpapers.com/images/hd/cool-profile-picture-minion-13pu7815v42uvrsg.jpg";
                }
                $type = 'New Follower';
                $url = 'astroera-astro://follower';
                FireBaseActionController::PushNOtification($getuserdata, 'New Follower', '' . $getdata->name . ' start following you', $us_imageuy, $type, $url);
            }
        } else {
            FollowModel::where('expert_id', $request->expert_id)
                ->where('user_id', $request->user_id)
                ->delete();
        }
        $result = FollowModel::where('expert_id', $request->expert_id)
            ->where('user_id', $request->user_id)
            ->exists();

        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'data' => $result
        ]);
    }


    public static  function MyFollowing($request, $user_Id)
    {

        $perPage = $request->input('per_page', 10);
        $astro = FollowModel::select([
            'users.id AS user_id',
            'users.name',
            'users.mobile',
            'users.image',
            'users.user_type',
            'users.astroera_account',
            'users_details.profile_name_en',
            'users_details.profile_name_hn',
            'users_details.specialisation',
            'users_details.languages',
            'users_details.experience',
            'users_details.is_login',
            'users_details.availability',
            'users_details.flags',
            'users_details.rating',
            'users_details.slug',
            'users_details.state',
            'users_details.country',
            'users_details.astro_call_charges',
            'users_details.astro_chat_charges',
            'users_details.disc_call_charge',
            'users_details.disc_chat_charge',
            'users_details.image_path',
            'users_details.profile_image',
            'users_details.is_promotional_accept',
            'users_details.label',
            'users_details.astro_video_charges',

            'users_details.video_commission',
            'users_details.disc_video_charge'

        ])->join('users', 'users.id', 'tbl_follow.expert_id')
            ->join('users_details', 'users_details.user_id', '=', 'users.id')
            ->where('tbl_follow.user_id', $user_Id)

            ->get();


        $experts = array();

        foreach ($astro as $astr) {

            $details['user_id'] = $astr->user_id;
            $details['name'] = $astr->name;
            $details['mobile'] = $astr->mobile;
            $details['image'] = image_url($astr->image, '/public/cms-images/user-images/');

            $details['user_type'] = $astr->user_type;
            $details['profile_name_en'] = $astr->profile_name_en;
            $details['profile_name_hn'] = $astr->profile_name_hn;
            $details['specialisation'] = explodespecialization($astr->specialisation);
            $details['languages'] = explodesLanguage($astr->languages);
            $details['experience'] = $astr->experience;
            $details['is_login'] = $astr->is_login;
            $details['availability'] = $astr->availability;
            $details['flags'] = $astr->flags;
            $details['rating'] = $astr->rating;
            $details['slug'] = $astr->slug;
            $details['state'] = $astr->state;
            $details['country'] = $astr->country;
            $details['astro_call_charges'] = $astr->astro_call_charges;
            $details['astro_chat_charges'] = $astr->astro_chat_charges;
            $details['disc_call_charge'] = $astr->disc_call_charge;
            $details['disc_chat_charge'] = $astr->disc_chat_charge;
            $details['astro_video_charges'] = $astr->astro_video_charges;
            $details['video_commission'] = $astr->video_commission;
            $details['disc_video_charge'] = $astr->disc_video_charge;


            $details['is_promotional_accept'] = $astr->is_promotional_accept;
            $details['label'] = getLabelName($astr->label);
            $details['skills'] = $astr->skills;
            $experts[] = $details;
        }


        return $experts;
    }
    public static function  DeleteAccount($request)
    {

        $authid = $request->auth_user->id;

        $user_data = User::where('id', $authid)->first();
        if ($user_data) {
            $user_data->status = 0;
            $user_data->is_deleted = 1;
            $user_data->save();
            if ($user_data->user_type == 'USER') {
                DB::select("UPDATE `users_details` SET `balance_amount` = '0' WHERE user_id = $authid");
                DB::select("UPDATE `call_chat_request` SET `delete_account` = '1' WHERE user_id = $authid");
                DB::select("UPDATE `checkouts` SET `delete_account` = '1' WHERE user_id = $authid");
            }

            return ApiResponse(200, true, 'Your account has been deleted successfully', $user_data);
        }


        return response()->json([
            'statusCode' => 403,
            'status' => false,
            'message' => 'failded to  Deleted Account',
        ]);
    }
}
