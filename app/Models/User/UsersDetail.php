<?php

namespace App\Models\User;

use App\Models\Commons\Cities;
use App\Models\Commons\Countries;
use App\Models\Commons\State;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\returnSelf;

class UsersDetail extends Model
{
    use HasFactory;

    public static $lang = "en";
    public function city()
    {
        return $this->belongsTo(Cities::class, 'city');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state');
    }

    public function country()
    {
        return $this->belongsTo(Countries::class, 'country');
    }

    public function label()
    {
        return $this->belongsTo(AstroLabel::class, 'label')->select('title', 'id');
    }
    public static function getChatAstroList($request, $type = "")
    {
        $sqlconditions = !empty($type) ? "FIND_IN_SET('$type', users_details.flags)" : "1=1";
        $perPage = $request->input('per_page', 10);
        if (!empty($request->lang)) {
            self::$lang = $request->lang;
        }

        $name = $request->query('search', '');
        $gender = $request->query('gender', '');
        $expericetype = ($request->query('experience') == 'ASC') ? 'ASC' : 'DESC';
        $pricetypeSort = ($request->query('price') == 'DESC') ? 'DESC' : 'ASC';

        $ratingtype = ($request->query('rating') == 'ASC') ? 'ASC' : 'DESC';
        $servicecharge = ($request->query('servicecharge') == 'ASC') ? 'ASC' : 'DESC';
        $language = $request->query('language', '');
        $specialisation = $request->query('specialisation', '');

        // Pre-fetch matched specialisation IDs
        $specialisationIds = DB::table('mst_specialisation')
            ->where(function ($query) use ($name) {
                $query->where('specialisation', 'LIKE', "%{$name}%")
                    ->orWhere('specialisation_hindi', 'LIKE', "%{$name}%");
            })
            ->pluck('id')
            ->toArray();

        $astroQuery = DB::table('users')
            ->select([
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
                'users_details.gender',
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
                DB::raw('AVG(review.rating) AS avg_review_rating')
            ])
            ->join('users_details', 'users_details.user_id', '=', 'users.id')
            ->leftJoin('review', 'review.to_experts', '=', 'users.id')
            ->join('mst_specialisation AS b', DB::raw('FIND_IN_SET(b.id, users_details.specialisation)'), '>', DB::raw('0'))
            ->where('users.status', 1)
            ->where('users.is_signup_complete', 1)
            ->where('users.user_type', 'ASTROLOGER')
            ->when($name, function ($query) use ($name, $specialisationIds) {
                $query->where(function ($q) use ($name, $specialisationIds) {
                    $q->where('users.name', 'LIKE', "%{$name}%")
                        ->orWhere('users_details.profile_name_en', 'LIKE', "%{$name}%")
                        ->orWhere('users_details.profile_name_hn', 'LIKE', "%{$name}%");
                    foreach ($specialisationIds as $id) {
                        $q->orWhereRaw("FIND_IN_SET(?, users_details.specialisation)", [$id]);
                    }
                });
            })
            ->when($gender, fn($q) => $q->where('users_details.gender', $gender))
            ->when($language, function ($query) use ($language) {
                $languageArray = explode(',', $language);
                $query->where(function ($q) use ($languageArray) {
                    foreach ($languageArray as $lang) {
                        $q->orWhereRaw("FIND_IN_SET(?, users_details.languages)", [$lang]);
                    }
                });
            })
            ->when($specialisation, function ($query) use ($specialisation) {
                $specialisationArray = explode(',', $specialisation);
                $query->where(function ($q) use ($specialisationArray) {
                    foreach ($specialisationArray as $id) {
                        $q->orWhereRaw("FIND_IN_SET(?, users_details.specialisation)", [$id]);
                    }
                });
            })
            ->whereRaw($sqlconditions);
        // if ($request->filled('experience')) {
        //     $condtions = $astroQuery->orderBy('users_details.experience', $expericetype);
        // } else {

        // }
        $condtions = $astroQuery
            ->orderBy('users_details.availability', 'DESC')
            ->orderBy('users_details.experience', $expericetype)

            ->orderBy('is_promotional_accept', 'DESC')
            ->orderBy('users_details.rating', $ratingtype)
            ->orderBy('users_details.disc_call_charge', 'ASC')
            ->orderBy('users_details.top_10s', 'ASC')
            ->orderByRaw('GREATEST(users_details.disc_call_charge, users_details.disc_chat_charge, users_details.disc_video_charge) ' . $servicecharge);
        $orderby = $condtions->groupBy('users.id')->paginate($perPage);

        $astroIds = $orderby->pluck('user_id')->toArray();
        $waitTimes = getWaitingTimeshortBulk($astroIds); // once only

        // --- OPTIMIZATION START ---
        // Collect IDs for bulk fetching
        $stateIds = [];
        $countryIds = [];
        $allSpecialisationIds = [];
        $allLanguageIds = [];

        foreach ($orderby as $item) {
            if ($item->state) $stateIds[] = $item->state;
            if ($item->country) $countryIds[] = $item->country;
            if ($item->specialisation) {
                $allSpecialisationIds = array_merge($allSpecialisationIds, explode(',', $item->specialisation));
            }
            if ($item->languages) {
                $allLanguageIds = array_merge($allLanguageIds, explode(',', $item->languages));
            }
        }

        $stateIds = array_unique($stateIds);
        $countryIds = array_unique($countryIds);
        $allSpecialisationIds = array_unique($allSpecialisationIds);
        $allLanguageIds = array_unique($allLanguageIds);

        // Fetch Data in Bulk
        $states = [];
        if (!empty($stateIds)) {
            $states = DB::table('mst_states')->whereIn('id', $stateIds)->pluck('state_name', 'id')->toArray();
        }

        $countries = [];
        if (!empty($countryIds)) {
            $countries = DB::table('mst_countries')->whereIn('id', $countryIds)->pluck('country_name', 'id')->toArray();
        }

        $specialisations = [];
        if (!empty($allSpecialisationIds)) {
            $specField = (self::$lang == 'hi') ? 'specialisation_hindi' : 'specialisation';
            $specialisations = DB::table('mst_specialisation')->whereIn('id', $allSpecialisationIds)->pluck($specField, 'id')->toArray();
        }

        $languages = [];
        if (!empty($allLanguageIds)) {
            $languages = DB::table('mst_languages')->whereIn('id', $allLanguageIds)->pluck('language_name', 'id')->toArray();
        }
        // --- OPTIMIZATION END ---

        $experts = [];

        foreach ($orderby as $astr) {
            $details['user_id'] = $astr->user_id;
            $details['name'] = (self::$lang == 'hi' && !empty($astr->profile_name_hn)) ? $astr->profile_name_hn : $astr->name;
            $details['mobile'] = $astr->mobile;
            $details['image'] = image_url($astr->image, $astr->image_path);
            $details['user_type'] = $astr->user_type;
            $details['gender'] = $astr->gender;
            $details['experience'] = $astr->experience;
            $details['is_login'] = $astr->is_login;
            $details['availability'] = $astr->availability;
            $details['flags'] = $astr->flags;
            $details['rating'] = $astr->rating;
            $details['slug'] = $astr->slug;

            // Optimized Lookups
            $details['state'] = $states[$astr->state] ?? null; // Was: getStateName($astr->state)
            $details['country'] = $countries[$astr->country] ?? null; // Was: getCountyName($astr->country)

            $details['astro_call_charges'] = $astr->astro_call_charges;
            $details['astro_chat_charges'] = $astr->astro_chat_charges;
            $details['disc_call_charge'] = $astr->disc_call_charge;
            $details['disc_chat_charge'] = $astr->disc_chat_charge;
            $details['is_promotional_accept'] = $astr->is_promotional_accept;
            $details['label'] = $astr->label;

            // Optimized Specialisation
            // Was: implode(',', explodespecialization($astr->specialisation, self::$lang));
            $specNames = [];
            if (!empty($astr->specialisation)) {
                $sIds = explode(',', $astr->specialisation);
                foreach ($sIds as $sid) {
                    if (isset($specialisations[$sid])) {
                        $specNames[] = $specialisations[$sid];
                    }
                }
            }
            $details['skills'] = implode(',', $specNames);

            // Optimized Languages
            // Was: explodesLanguage($astr->languages);
            $langNames = [];
            if (!empty($astr->languages)) {
                $lIds = explode(',', $astr->languages);
                foreach ($lIds as $lid) {
                    if (isset($languages[$lid])) {
                        $langNames[] = $languages[$lid];
                    }
                }
            }
            $details['language_name'] = implode(',', $langNames);


            $details['wait_time'] = $waitTimes[$astr->user_id] ?? 0;
            $details['avg_review_rating'] = round($astr->avg_review_rating, 2);
            $experts[] = $details;
        }

        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'data' => $experts,
            'pagination' => [
                'total' => $orderby->total(),
                'current_page' => $orderby->currentPage(),
                'last_page' => $orderby->lastPage(),
                'per_page' => $orderby->perPage(),
                'from' => $orderby->firstItem(),
                'to' => $orderby->lastItem(),
            ]
        ]);
    }




    public static  function getAstroList($sqlconditions = [])
    {

        // $typedata = !empty($type) ? "FIND_IN_SET('$type', users_details.flags)" : "";

        $perPage = 10;

        $astro = DB::table('users')
            ->select([
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
                DB::raw('AVG(review.rating) as avg_review_rating'),


            ])
            ->leftJoin('users_details', 'users_details.user_id', '=', 'users.id')
            ->leftJoin('review', 'review.to_experts', '=', 'users.id') // Join with reviews table
            ->join('mst_specialisation AS b', DB::raw('FIND_IN_SET(b.id, users_details.specialisation)'), '>', DB::raw('0'))
            ->whereNotIn('users.id', [2, 68, 45, 89, 90, 5377])
            ->where('users.status', 1)
            ->where('users.is_signup_complete', 1)
            ->where('users.user_type', 'ASTROLOGER')
            ->where('users.astroera_account', 0)



            ->when($sqlconditions, function ($query, $querydata) {

                for ($i = 0; $i < count($querydata); $i++) {
                    $query->whereRaw($querydata[$i]);
                }
            })

            ->orderBy('users_details.availability', 'DESC')
            ->orderBy('users_details.disc_call_charge', 'ASC')
            ->orderBy('users_details.top_10s', 'ASC')
            ->groupBy('users.id')


            ->paginate($perPage);

        $experts = array();



        foreach ($astro as $astr) {

            $details['user_id'] = $astr->user_id;
            $details['name'] = $astr->name;
            $details['language_name'] = $astr->languages;


            if (self::$lang == 'hi' && !empty($astr->profile_name_hn)) {
                $details['name'] = $astr->profile_name_hn;
            }

            $details['mobile'] = $astr->mobile;
            $details['image'] = image_url($astr->image, $astr->image_path);


            $details['user_type'] = $astr->user_type;

            $details['experience'] = $astr->experience;
            $details['is_login'] = $astr->is_login;
            $details['availability'] = $astr->availability;
            $details['flags'] = $astr->flags;
            $details['rating'] = $astr->rating;
            $details['slug'] = $astr->slug;
            $details['state'] = getStateName($astr->state);
            $details['country'] = getCountyName($astr->country);
            $details['astro_call_charges'] = $astr->astro_call_charges;
            $details['astro_chat_charges'] = $astr->astro_chat_charges;
            $details['disc_call_charge'] = $astr->disc_call_charge;
            $details['disc_chat_charge'] = $astr->disc_chat_charge;
            $details['is_promotional_accept'] = $astr->is_promotional_accept;
            $details['label'] = $astr->label;
            $details['skills'] = implode(',', explodespecialization($astr->specialisation, self::$lang));
            $details['language_name'] = explodesLanguage($astr->languages);
            $details['wait_time'] = getWaitingTimeshort($astr->user_id);
            $details['avg_review_rating'] = round($astr->avg_review_rating, 2);
            $experts[] = $details;
        }

        return $experts;
    }

    public static function getAstroListSimple($sqlconditions = [])
    {
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
                DB::raw('(SELECT AVG(rating) FROM review WHERE to_experts = users.id) AS avg_review_rating'),
            ])
            ->leftJoin('users_details', 'users_details.user_id', '=', 'users.id')
            ->whereNotIn('users.id', [2, 68, 45, 89, 90, 5377])
            ->where('users.status', 1)
            ->where('users.is_signup_complete', 1)
            ->where('users.user_type', 'ASTROLOGER')
            ->where('users.astroera_account', 0)
            ->when($sqlconditions, function ($query, $querydata) {
                foreach ($querydata as $raw) {
                    $query->whereRaw($raw);
                }
            })
            ->orderBy('users_details.availability', 'DESC')
            ->limit(10)
            ->get();

        $experts = [];

        foreach ($astro as $astr) {
            $details['user_id'] = $astr->user_id;
            $details['name'] = self::$lang == 'hi' && !empty($astr->profile_name_hn) ? $astr->profile_name_hn : $astr->name;
            $details['mobile'] = $astr->mobile;
            $details['image'] = image_url($astr->image, '/public/cms-images/user-images/');
            $details['user_type'] = $astr->user_type;
            $details['experience'] = $astr->experience;
            $details['is_login'] = $astr->is_login;
            $details['availability'] = $astr->availability;
            $details['flags'] = $astr->flags;
            $details['rating'] = $astr->rating;
            $details['slug'] = $astr->slug;
            $details['state'] = getStateName($astr->state);
            $details['country'] = getCountyName($astr->country);
            $details['astro_call_charges'] = $astr->astro_call_charges;
            $details['astro_chat_charges'] = $astr->astro_chat_charges;
            $details['disc_call_charge'] = $astr->disc_call_charge;
            $details['disc_chat_charge'] = $astr->disc_chat_charge;
            $details['is_promotional_accept'] = $astr->is_promotional_accept;
            $details['label'] = $astr->label;

            $details['skills'] = implode(',', explodespecialization($astr->specialisation, self::$lang));
            $details['language_name'] = explodesLanguage($astr->languages);
            // $details['wait_time'] = getWaitingTimeshort($astr->user_id);
            $details['avg_review_rating'] = round($astr->avg_review_rating, 2);

            $experts[] = $details;
        }

        return $experts;
    }

    public static function getAstroListSimple2($sqlconditions = [])
    {
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
                DB::raw('(SELECT AVG(rating) FROM review WHERE to_experts = users.id) AS avg_review_rating'),
            ])
            ->leftJoin('users_details', 'users_details.user_id', '=', 'users.id')
            ->whereNotIn('users.id', [2, 68, 45, 89, 90, 5377])
            ->where('users.status', 1)
            ->where('users.is_signup_complete', 1)
            ->where('users.user_type', 'ASTROLOGER')
            ->where('users.astroera_account', 0)
            ->when($sqlconditions, function ($query, $querydata) {
                foreach ($querydata as $raw) {
                    $query->whereRaw($raw);
                }
            })
            ->orderBy('users.name', 'ASC')
            ->limit(10)
            ->get();

        $experts = [];

        foreach ($astro as $astr) {
            $details['user_id'] = $astr->user_id;
            $details['name'] = self::$lang == 'hi' && !empty($astr->profile_name_hn) ? $astr->profile_name_hn : $astr->name;
            $details['mobile'] = $astr->mobile;
            $details['image'] = image_url($astr->image, '/public/cms-images/user-images/');
            $details['user_type'] = $astr->user_type;
            $details['experience'] = $astr->experience;
            $details['is_login'] = $astr->is_login;
            $details['availability'] = $astr->availability;
            $details['flags'] = $astr->flags;
            $details['rating'] = $astr->rating;
            $details['slug'] = $astr->slug;
            $details['state'] = getStateName($astr->state);
            $details['country'] = getCountyName($astr->country);
            $details['astro_call_charges'] = $astr->astro_call_charges;
            $details['astro_chat_charges'] = $astr->astro_chat_charges;
            $details['disc_call_charge'] = $astr->disc_call_charge;
            $details['disc_chat_charge'] = $astr->disc_chat_charge;
            $details['is_promotional_accept'] = $astr->is_promotional_accept;
            $details['label'] = $astr->label;

            $details['skills'] = implode(',', explodespecialization($astr->specialisation, self::$lang));
            $details['language_name'] = explodesLanguage($astr->languages);
            // $details['wait_time'] = getWaitingTimeshort($astr->user_id);
            $details['avg_review_rating'] = round($astr->avg_review_rating, 2);

            $experts[] = $details;
        }

        return $experts;
    }

    public static function getAstroListSimple3($sqlconditions = [], $chatCountSubquery = null)
    {
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
                DB::raw('(SELECT AVG(rating) FROM review WHERE to_experts = users.id) AS avg_review_rating'),
                DB::raw('IFNULL(cc.chat_count, 0) AS chat_count')  // 👈 chat count
            ])
            ->leftJoin('users_details', 'users_details.user_id', '=', 'users.id')
            ->leftJoinSub($chatCountSubquery, 'cc', 'cc.expert_id', '=', 'users.id') // 👈 join subquery
            ->whereNotIn('users.id', [2, 68, 45, 89, 90, 5377])
            ->where('users.status', 1)
            ->where('users.is_signup_complete', 1)
            ->where('users.user_type', 'ASTROLOGER')
            ->where('users.astroera_account', 0)
            ->when($sqlconditions, function ($query, $querydata) {
                foreach ($querydata as $raw) {
                    $query->whereRaw($raw);
                }
            })
            ->orderByDesc('chat_count')
            ->orderBy('users.name', 'ASC') // 👈 order by user name
            ->limit(10)
            ->get();

        $experts = [];

        foreach ($astro as $astr) {
            $details['user_id'] = $astr->user_id;
            $details['name'] = self::$lang == 'hi' && !empty($astr->profile_name_hn) ? $astr->profile_name_hn : $astr->name;
            $details['mobile'] = $astr->mobile;
            $details['image'] = image_url($astr->image, '/public/cms-images/user-images/');
            $details['user_type'] = $astr->user_type;
            $details['experience'] = $astr->experience;
            $details['is_login'] = $astr->is_login;
            $details['availability'] = $astr->availability;
            $details['flags'] = $astr->flags;
            $details['rating'] = $astr->rating;
            $details['slug'] = $astr->slug;
            $details['state'] = getStateName($astr->state);
            $details['country'] = getCountyName($astr->country);
            $details['astro_call_charges'] = $astr->astro_call_charges;
            $details['astro_chat_charges'] = $astr->astro_chat_charges;
            $details['disc_call_charge'] = $astr->disc_call_charge;
            $details['disc_chat_charge'] = $astr->disc_chat_charge;
            $details['is_promotional_accept'] = $astr->is_promotional_accept;
            $details['label'] = $astr->label;

            $details['skills'] = implode(',', explodespecialization($astr->specialisation, self::$lang));
            $details['language_name'] = explodesLanguage($astr->languages);
            // $details['wait_time'] = getWaitingTimeshort($astr->user_id);
            $details['avg_review_rating'] = round($astr->avg_review_rating, 2);

            $experts[] = $details;
        }

        return $experts;
    }

    public function getSpecialisationNamesAttribute()
    {
        $specialisationIds = explode(',', $this->specialisation);

        return DB::table('mst_specialisation')
            ->whereIn('id', $specialisationIds)
            ->select('specialisation as english_name', 'specialisation_hindi as hindi_name')->get()->toArray();
    }

    public function getLanguageNamesAttribute()
    {
        $languagesIds = explode(',', $this->languages);

        return DB::table('mst_languages')
            ->whereIn('id', $languagesIds)
            ->select('language_name as english_name', 'language_name_hindi as hindi_name')->get()->toArray();
    }



    public static  function getAstroProfile($request, $astroId)
    {
        $tokenid = $astroId;


        $expert = User::with([
            'userDetail.city',
            'userDetail.state',
            'userDetail.country',
            'userDetail.label',
            'ReviewBy',

            'Gallery'

        ])
            ->select(
                'users.id as user_id',
                'users.id',
                'users.name',
                'users.mobile',
                'users.image',
                'users.user_type',

            )

            ->where('users.id', $astroId)
            ->where('users.status', 1)
            ->where('users.user_type', 'ASTROLOGER')
            ->first();


        if ($expert) {

            $expert->userDetail->specialisation = $expert->userDetail->specialisation_names;
            $expert->userDetail->languages = $expert->userDetail->language_names;
            $token = request()->bearerToken();
            $expert->userDetail->is_following = false;

            $expert->userDetail->image = image_url($expert->image, $expert->userDetail->image_path);

            if ($token) {
                $user = JWTAuth::parseToken()->authenticate();

                if (!empty($user)) {

                    $expert->userDetail->is_following = $expert->isFollowing($user->id);
                }
            }
            $expert->image = image_url($expert->image, $expert->userDetail->image_path);
            $expert->Gallery->transform(function ($gallery) {
                $gallery->full_image_url = image_url($gallery->image, $gallery->image_path);
                return $gallery;
            });

            if ($expert->ReviewBy) {
                $expert->ReviewBy->transform(function ($review) {
                    if ($review->user) {
                        $review->user->image = image_url($review->user->image, '/public/cms-images/user-images/');
                    }
                    return $review;
                });
            }
            //$expert->wait_time = getWaitingTimeshort($expert->id);

            // ⭐ Add avg review rating here
            $avgRating = DB::table('review')
                ->where('to_experts', $astroId)
                ->avg('rating');
            $expert->avg_review_rating = $avgRating ? round($avgRating, 1) : 0.0;
            $expert->userDetail->rating = $avgRating ? round($avgRating, 1) : 0.0;

            return response()->json([
                'statusCode' => 200,
                'status' => true,
                'message' => 'success',
                'data' => $expert
            ]);
        }
        return response()->json([
            'statusCode' => 403,
            'status' => false,
            'message' => 'astro not found/or not active ',
        ]);
    }
}
