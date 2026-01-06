<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Common\CommonController;
use App\Http\Controllers\Controller;
use App\Models\Blogs;
use App\Models\Commons\Horoscope;
use App\Models\EnquiryModel;
use App\Models\PoojaModel;
use App\Models\User\BannerImage;
use Illuminate\Http\Request;
use Validator;
use App\Models\User\User;
use App\Models\User\UsersDetail;
use Illuminate\Support\Facades\DB;

class HomePageController extends CommonController
{
    //
 
	  public function SimilarAstrologer(Request $request)
	{
		$excludeId = $request->expert_id;
		$perPage = $request->input('per_page', 10);
		$type = $request->input('type');
		$sqlconditions = !empty($type) ? "FIND_IN_SET('$type', users_details.flags)" : "1=1";

		$astro = DB::table('users')
			->select([
				'users.id AS user_id',
				'users.name',
				'users.mobile',
				'users.image',
				'users.user_type',
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
				DB::raw('COALESCE(r.avg_review_rating, 0) as avg_review_rating'),
			])
			->leftJoin('users_details', 'users_details.user_id', '=', 'users.id')
			->leftJoin(DB::raw('(SELECT to_experts, AVG(rating) as avg_review_rating FROM review GROUP BY to_experts) as r'), 'r.to_experts', '=', 'users.id')
			->whereNotIn('users.id', [2, 68, 45, 89, 90, 5377])
			->where('users.id', '!=', $excludeId)
			->where('users.status', 1)
			->where('users.is_signup_complete', 1)
			->where('users.user_type', 'ASTROLOGER')
			->where('users.astroera_account', 0)
			->whereRaw($sqlconditions)
			->orderBy('users_details.availability', 'DESC')
			->orderBy('users_details.disc_call_charge', 'ASC')
			->orderBy('users_details.top_10s', 'ASC')
			->paginate($perPage);

		$experts = [];

		foreach ($astro as $astr) {
			$experts[] = [
				'user_id' => $astr->user_id,
				'name' => $astr->name,
				'mobile' => $astr->mobile,
				'image' => image_url($astr->image,'/public/cms-images/user-images/'),
				'user_type' => $astr->user_type,
				'profile_name_en' => $astr->profile_name_en,
				'profile_name_hn' => $astr->profile_name_hn,
				'specialisation' => $astr->specialisation,
				'languages' => $astr->languages,
				'experience' => $astr->experience,
				'is_login' => $astr->is_login,
				'availability' => $astr->availability,
				'flags' => $astr->flags,
				'rating' => $astr->rating,
				'slug' => $astr->slug,
				'state' => $astr->state,
				'country' => $astr->country,
				'astro_call_charges' => $astr->astro_call_charges,
				'astro_chat_charges' => $astr->astro_chat_charges,
				'disc_call_charge' => $astr->disc_call_charge,
				'disc_chat_charge' => $astr->disc_chat_charge,
				'profile_image' => $astr->profile_image,
				'is_promotional_accept' => $astr->is_promotional_accept,
				'label' => $astr->label,
				'avg_review_rating' => round($astr->avg_review_rating, 2),
			];
		}

		return response()->json([
			'statusCode' => 200,
			'status' => true,
			'message' => 'success',
			'data' => $experts,
			'pagination' => [
				'total' => $astro->total(),
				'current_page' => $astro->currentPage(),
				'last_page' => $astro->lastPage(),
				'per_page' => $astro->perPage(),
				'from' => $astro->firstItem(),
				'to' => $astro->lastItem(),
			]
		]);
	}

    
    public function getHomePageBanners(Request $request){
  
        $perPage = $request->input('per_page', 10);
        $homeBanners =  BannerImage::paginate($perPage)->map(function($item){
           
            //$item->full_pathimage=asset($item->image_path.$item->image);
    $item->full_pathimage = image_url($item->image,'/public/cms-images/user-images/');

			
            return $item;
    
        });
  


        if(!$homeBanners){
            return response()->json([
                'statusCode'=>404,
                'status' => false,
                'message' => 'not found',
            ]);
        }
        return response()->json([
            'statusCode'=>200,
            'status' => true,
            'message' => 'success',
            'data'=>$homeBanners,
        ]);

    }
	
	 public function getHomepageAstrologer(Request $request){
        $yesterday=date('Y-m-d',strtotime("-1 days"));
       
        $liveastro = DB::select("SELECT MIN(ud.user_id) as user_id, nccr.expert_id 
        FROM call_chat_request as nccr
        LEFT JOIN users_details as ud ON nccr.expert_id = ud.user_id
        LEFT JOIN users as u ON ud.user_id = u.id
        WHERE u.astroera_account = 0 
        AND nccr.request_status = 5 
        AND ud.availability = 1  
        GROUP BY nccr.expert_id 
        LIMIT 15");
   

        $first=[];
        foreach($liveastro as $liveastros) {
            $first[]=$liveastros->user_id;
        } 
        $values = $first;
        $array = array_fill(0, 3, array_fill(0, 5, null));
        $index = 0;
        if(!empty($values)){
            for ($i = 0; $i < 3; $i++) {
                for ($j = 0; $j < 5; $j++) {
                    $array[$i][$j] = $values[$index % count($values)];
                    $index++;
                }
            }
        }
      
        $caseOne=implode(',',$array[0]);
        $caseTwo=implode(',',$array[1]);
        $caseThree=implode(',',$array[2]);
        // $case0=$array[0];
        // $case1=$array[1];
        // $case2=$array[2];


        $condtionsgetonline=[("users.id IN ($caseTwo)")];
        $condtionsgetverified=[("users.id IN ($caseOne)")];

        $condtionsgettrending=[ 'users.status=1',("users.id IN ($caseThree)"),'users_details.availability=1','users.astroera_account=0'];
        $astrologer=[];
        if(!empty($values)){

    
        $astrologer['online'] =UsersDetail::getAstroList($condtionsgetonline);
        $astrologer['verified']=UsersDetail::getAstroList($condtionsgetverified);
        $astrologer['trending']=UsersDetail::getAstroList($condtionsgettrending);

    }



              
        if(!$astrologer){
            return response()->json([
                'statusCode'=>404,
                'status' => false,
                'message' => 'not found',
            ]);
        }
        return response()->json([
            'statusCode'=>200,
            'status' => true,
            'message' => 'success',
            'data'=>$astrologer,
        ]);

    }
	
public function getOnlineAstrologers()
    {
        $sqlconditions = [
			"users_details.availability = 1"
		];

		return response()->json([
			'statusCode' => 200,
			'status' => true,
			'message' => 'success',
			'data' => UsersDetail::getAstroListSimple($sqlconditions)
		]);
    }


	public function getVerifiedAstrologers()
	{
		$ids = DB::table('call_chat_request as nccr')
            ->join('users_details as ud', 'nccr.expert_id', '=', 'ud.user_id')
            ->join('users as u', 'ud.user_id', '=', 'u.id')
            ->where('u.astroera_account', 0)
            ->where('nccr.request_status', 5)
            ->where('ud.availability', 1)
            ->select(DB::raw('DISTINCT nccr.expert_id'))
            ->pluck('expert_id')
            ->toArray();

        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'data' => UsersDetail::getAstroListSimple2(['users.id IN (' . implode(',', $ids) . ')'])
        ]);
	
	}



	public function getTrendingAstrologers()
	{
		$trending = DB::table('call_chat_request')
			->select('expert_id', DB::raw('COUNT(*) as chat_count'))
			->where('request_status', 5)
			->groupBy('expert_id');

		$sqlconditions = [
			"users_details.availability = 1"
		];

		return response()->json([
			'statusCode' => 200,
			'status' => true,
			'message' => 'success',
			'data' => UsersDetail::getAstroListSimple3($sqlconditions, $trending)
		]);
	}


   
    public function getBlogs(Request $request){
        $data=Blogs::getBlogsList($request);
        return response()->json([
            'statusCode'=>200,
            'status' => true,
            'message' => 'success',
            'data'=>$data,
        ]);
    }

    public static function  sendFeedbackToCeo(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'description' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode'=>403,
                'status'=>false,
                'message'=>'Please Fill Mandatory fields',
                'errors'=>$validator->errors()
            ]);
        }

        $user=User::find($request->user_id);
            if(!$user){
                return response()->json([
                    'statusCode'=>403,
                    'status'=>false,
                    'message'=>'user does not exits',
                ]);
            }
        $postfeedback=EnquiryModel::PostEnquiry($user->name,$user->email,$user->mobile,'customer',$request->description,'enquiry',$status=0);
        return $postfeedback;

    }
    public function  getHoroscope(Request $request){    
        $result=Horoscope::getList();
        return $result; 
    }
    public function getHoroscopeData($type, $sign){
        $result=Horoscope::getHoroscopeData($type, $sign);
        return $result;
    }
    public function getBlogDetails($id){
        $getdata=Blogs::find($id);
        if($getdata){
            if(!empty($getdata->media_file)){
               // $getdata->image=asset($getdata->file_path.$getdata->media_file);
				$getdata->image = image_url($getdata->media_file,'/public/cms-images/blogs/');
            }
            return ApiResponse(200,true,"success",$getdata);
        }
        return SimpleResponse(404,false,"Not found");
    }
    public function getHomepagePooja(){
        $data=PoojaModel::getHomePagePooja();
        return ApiResponse(200,true,"success",$data);
    }
}
