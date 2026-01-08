<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CallChatRequest extends Model
{
    use HasFactory;
    protected $table = 'call_chat_request';

    protected $fillable = [
        'user_id',
        'expert_id',
        'user_name',
        'request_type',
        'chat_commission',
        'astro_chat_charge',
        'waitlist_status',
        'form_meta',
        'device_type',
        'is_promotional',
        'request_session_id',
        'start_session_date',
        'request_expired',
        'request_status',
        'astro_call_chagre',
        'call_commission',
        'user_start_time',
        'user_end_time',
        'astro_start_time',
        'astro_end_time',
        'total_duration',
        'astro_call_chagre',
        'astro_video_call_charge',
        'video_commission',
        'web_token',
        'new_api'
    ];

    public static function CancelActiveAllCallChat($userid)
    {
        $checkschedulechat = CallChatRequest::where("user_id", $userid)
            ->where("request_status", 1)
            ->first();
        if ($checkschedulechat) {
            $checkschedulechat->request_status = "7";
            $checkschedulechat->save();
        }
    }

    public static function getConsultHistory($request, $user_id)
    {
        $request_status = $request->request_status;
        $type = $request->type;
        $consult = $request->consult;

        $callList = Self::leftJoin('users', 'users.id', '=', 'call_chat_request.user_id')
            ->leftJoin('wallets', function ($join) {
                $join->on('wallets.transaction_id', '=', 'call_chat_request.request_session_id')
                    ->whereColumn('wallets.user_id', 'call_chat_request.user_id');
            })
            ->join('users as expert_users', 'expert_users.id', '=', 'call_chat_request.expert_id')
            ->join('users_details', 'users_details.user_id', '=', 'call_chat_request.expert_id')
            ->leftJoin('mst_order_status', 'mst_order_status.order_status_id', '=', 'call_chat_request.request_status')

            ->leftJoin('review', function ($join) use ($user_id) {
                $join->on('review.to_experts', '=', 'call_chat_request.expert_id')
                    ->where('review.user_id', '=', $user_id)
                    ->whereColumn('review.consult_id', 'call_chat_request.request_session_id');
            })

            ->leftJoin('consult_remedies', 'consult_remedies.consult_it', '=', 'call_chat_request.request_session_id') // 🔁 JOIN for remedies

            // ✅ Apply remedies filter only if consult = 1
            ->when($consult == 1, function ($query) {
                $query->whereNotNull('consult_remedies.id');
            })

            ->select(
                'expert_users.image as image',
                'call_chat_request.id',
                'users_details.profile_name_en as expert_name',
                'call_chat_request.expert_id',
                'users_details.disc_chat_charge as expert_chat_charge',
                'users_details.disc_call_charge as expert_call_charge',
                'wallets.amount as total_service_charge',
                'call_chat_request.request_type',
                'mst_order_status.name as status',
                'call_chat_request.request_status_log as ended_by',
                'call_chat_request.request_session_id as consult_id',
                'call_chat_request.user_start_time as start_time',
                'call_chat_request.user_end_time as end_time',
                'call_chat_request.total_duration as total_seconds',
                'call_chat_request.astro_video_call_charge',
                'call_chat_request.astro_chat_charge',
                'call_chat_request.astro_call_chagre',
                'call_chat_request.record_url',
                'call_chat_request.is_promotional',
                'review.comments as user_review_comment',
                'review.comment_reply as astro_reply_comment',
                'review.rating as user_rating'
            )
            ->where('call_chat_request.user_id', $user_id)
            ->where('users.user_type', 'USER')
            ->when($type, function ($query, $type) {
                $query->where('call_chat_request.request_type', $type);
            })
            ->when($request_status, function ($query, $request_status) {
                $query->where('call_chat_request.request_status', $request_status);
            })
            ->orderByRaw("FIELD(call_chat_request.request_status, 20) DESC")
            ->orderBy('call_chat_request.id', 'DESC')
            ->groupBy('call_chat_request.id')
            ->paginate(10)
            ->through(function ($item) {
                $item->remedies = \App\Models\ConsultRemedies::where('consult_it', $item->consult_id)->exists();

                $item->image = image_url($item->image, '/public/cms-images/user-images/');

                $item->stream_url = !empty($item->record_url)
                    ? route('stream-recording', ['url' => $item->record_url])
                    : null;

                return $item;
            });

        return $callList;
    }

    public static function getConsultHistoryhome($request, $user_id)
    {
        $request_status = $request->request_status;
        $type = $request->type;

        $callList = Self::leftJoin('users', 'users.id', '=', 'call_chat_request.user_id')
            ->leftJoin('wallets', function ($join) {
                $join->on('wallets.transaction_id', '=', 'call_chat_request.request_session_id')
                    ->whereColumn('wallets.user_id', 'call_chat_request.user_id');
            })
            ->join('users as expert_users', 'expert_users.id', '=', 'call_chat_request.expert_id')
            ->join('users_details', 'users_details.user_id', '=', 'call_chat_request.expert_id')
            ->leftJoin('mst_order_status', 'mst_order_status.order_status_id', '=', 'call_chat_request.request_status')

            ->leftJoin('review', function ($join) use ($user_id) {
                $join->on('review.to_experts', '=', 'call_chat_request.expert_id')
                    ->where('review.user_id', '=', $user_id)
                    ->whereColumn('review.consult_id', 'call_chat_request.request_session_id');
            })

            ->select(
                'expert_users.image as image',
                'call_chat_request.id',
                'users_details.profile_name_en as expert_name',
                'call_chat_request.expert_id',
                'users_details.disc_chat_charge as expert_chat_charge',
                'users_details.disc_call_charge as expert_call_charge',
                'wallets.amount as total_service_charge',
                'call_chat_request.request_type',
                'mst_order_status.name as status',
                'call_chat_request.request_status_log as ended_by',
                'call_chat_request.request_session_id as consult_id',
                'call_chat_request.user_start_time as start_time',
                'call_chat_request.user_end_time as end_time',
                'call_chat_request.total_duration as total_seconds',
                'call_chat_request.astro_video_call_charge',
                'call_chat_request.astro_chat_charge',
                'call_chat_request.astro_call_chagre',
                'call_chat_request.record_url',
                'call_chat_request.is_promotional',
                'review.comments as user_review_comment',
                'review.comment_reply as astro_reply_comment',
                'review.rating as user_rating'
            )
            ->where('call_chat_request.user_id', $user_id)
            ->where('users.user_type', 'USER')
            ->whereNotIn('call_chat_request.request_status', [7, 8, 9, 10])
            ->when($type, function ($query, $type) {
                $query->where('call_chat_request.request_type', $type);
            })
            ->when($request_status, function ($query, $request_status) {
                $query->where('call_chat_request.request_status', $request_status);
            })
            ->orderByRaw("FIELD(call_chat_request.request_status, 20) DESC")
            ->orderBy('call_chat_request.id', 'DESC')
            ->groupBy('call_chat_request.id')
            ->paginate(10)
            ->through(function ($item) {
                $item->remedies = \App\Models\ConsultRemedies::where('consult_it', $item->consult_id)->exists();


                $item->image = image_url($item->image, '/public/cms-images/user-images/');
                $item->stream_url = !empty($item->record_url)
                    ? route('stream-recording', ['url' => $item->record_url])
                    : null;
                return $item;
            });

        return $callList;
    }


    public static function getConsultHistoryAstrologer($request, $user_id)
    {
        $request_status = $request->request_status;
        $type = $request->type;

        $callList = Self::join('users', 'users.id', '=', 'call_chat_request.expert_id')
            ->join('wallets', function ($join) {
                $join->on('wallets.transaction_id', '=', 'call_chat_request.request_session_id')
                    ->whereColumn('wallets.user_id', 'call_chat_request.expert_id');
            })
            ->join('users_details', 'users_details.user_id', '=', 'call_chat_request.expert_id')
            ->join('mst_order_status', 'mst_order_status.order_status_id', '=', 'call_chat_request.request_status')
            ->select(
                'users.image',
                'call_chat_request.id',
                'users_details.profile_name_en as expert_name',
                'users_details.user_id as expert_id',
                'users_details.disc_chat_charge as expert_chat_charge',
                'users_details.disc_call_charge as expert_call_charge',
                'wallets.amount as total_service_charge',
                'call_chat_request.request_type',
                'mst_order_status.name as status',
                'call_chat_request.request_status_log as ended_by',
                'call_chat_request.request_session_id as consult_id',
                'call_chat_request.user_start_time as start_time',
                'call_chat_request.user_end_time as end_time',
                'call_chat_request.total_duration as total_seconds',
                'call_chat_request.astro_video_call_charge',
                'users.name as experts_name'
            )
            ->where('call_chat_request.expert_id', $user_id)
            ->when($type, function ($query, $type) {
                $query->where('call_chat_request.request_type', $type);
            })
            ->when($request_status, function ($query, $request_status) {
                $query->where('call_chat_request.request_status', $request_status);
            })
            ->orderByRaw("FIELD(call_chat_request.request_status, 20) DESC")
            ->orderby('call_chat_request.id', 'DESC')
            ->groupby('call_chat_request.id')
            ->paginate(10)
            ->through(function ($item) {  // Use through() instead of map()
                $item->remedies = ConsultRemedies::where('consult_it', $item->consult_id)->exists();
                $item->image = image_url($item->image, '/public/cms-images/user-images/');

                return $item;
            });

        return $callList;
    }

    public static function ChatHistory($request, $consultId)
    {

        $data = ChatMessages::where('request_session_id', $consultId)->select('user_id', 'id', 'message', 'messageId', 'time', 'fileurl as image', 'request_session_id as room_id', 'status')->orderBy("time", "DESC")->paginate(50);
        return $data;
    }
}
