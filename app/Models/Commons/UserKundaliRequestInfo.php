<?php

namespace App\Models\Commons;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserKundaliRequestInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'k_name',
        'k_type',
        'k_birth_place',
        'k_latitude',
        'k_longitude',
        'k_timezone',
        'k_birth_time',
        'k_country',
        'k_dob',
        'k_gender',
        'k_language',
        'k_email',
        'k_mobile',
    ];
    public static function index($request,$userid){
        $perPage = $request->input('per_page', 10);
        $obj = UserKundaliRequestInfo::where('user_id',$userid)->orderBy('updated_at', 'desc')->paginate($perPage);
        return $obj;
    }
    public static function saveRecod($request){
            // dd(date('H:i:s', strtotime($request->birth_time)));
            $locationdata=getLocationByLatLong($request->latitude,$request->longitude);
            $userdata=User::find($request->user_id);
            $obj = UserKundaliRequestInfo::updateOrCreate([
                'user_id'      => $request->user_id,
                'k_name'       => $request->name,
                'k_type'       => 'FreeLandingKundali',
                'k_birth_place'=> $request->birth_place,
                'k_latitude'   => $request->latitude,
                'k_longitude'  => $request->longitude,
                'k_timezone'   => $request->time_zone,
                'k_birth_time' => date('H:i:s', strtotime($request->birth_time)),
                'k_country'    => !empty($locationdata['country']['name']) ? $locationdata['country']['name'] : "India",
                'k_dob'        => $request->birth_date,
                'k_gender'     => $request->gender,
                'k_language'   => 1,
                'k_email'      => $userdata->email,
                'k_mobile'     => $userdata->mobile,
            ]);

            $obj->touch();
            return $obj;

    }
}
