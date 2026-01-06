<?php
namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserDetailsModel;
use App\Models\ProductsModel;
use App\Models\User\User;

use DB;

class ReviewsModel extends Model
{
    use HasFactory;

    protected $table = 'review';

    protected $fillable = [
        'product_id',
        'user_id',
        'to_experts',
        'item_type',
        'rating',
        'comments',
        'consult_id',
        'usr_name',
        'status',
    ];

    public static function saveReview($request)
    {
        $obj = new self();

        $obj->user_id    = $request->user_id;
        $obj->to_experts = $request->expert_id;
        $obj->consult_id = $request->consult_id;
        $obj->comments   = $request->comments;
        $obj->status     = 0;

        return $obj->save();
    }


    public static function getAstroReview($astroid){
        $result = self::selectRaw('SUM(rating) as grandTotal, COUNT(to_experts) as total')
        ->where('to_experts', $astroid)
        ->first();
        if ($result->grandTotal > 0) {
            $rating['total']   = number_format($result->grandTotal / $result->total, 1);
            $rating['totalNo'] = $result->total;
        } else {
            $rating['total']   = 1;
            $rating['totalNo']  = 1;
        }

     return $rating;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

   
}