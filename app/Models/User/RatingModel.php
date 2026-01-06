<?php
namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class RatingModel extends Model
{
    use HasFactory;

    protected $table = 'review';

    protected $fillable = [
        'product_id',
        'user_id',
        'usr_name',
        'to_experts',
        'item_type',
        'rating',
        'comments',
        'comment_reply',
        'consult_id',
        'is_anonymous',
        'status'
    ];

    public static function anonymous_status($request)
    {
        $userId = $request->user_id;
        $review_id = $request->review_id;
        $expertId = $request->expert_id;
        $isAnonymous = $request->is_anonymous;

        // Find the review
        $review = DB::table('review')
            ->where('id', $review_id)
            ->where('user_id', $userId)
            ->where('to_experts', $expertId)
            ->first();

        if ($review) {
            // Update is_anonymous
            return DB::table('review')
                ->where('id', $review_id)
                ->update([
                    'is_anonymous' => $isAnonymous,
                    'updated_at' => now()
                ]);
        }

        return false;
    }


    public static function saveRating($request)
    {
        $obj = new self();
        $obj->user_id = $request->user_id;
        $obj->to_experts = $request->expert_id;
        $obj->consult_id = $request->consult_id;
        $obj->rating = $request->rating;
        $obj->status = 0;
        if (auth()->user()) {
            $obj->usr_name = auth()->user()->name ?? null;
        }
        if ($obj->save()) {
            return $obj->id; 
        }

        return false;
    }

    public static function getAstroRating($astroid)
    {
        $result = self::selectRaw('SUM(rating) as grandTotal, COUNT(to_experts) as total')
                    ->where('to_experts', $astroid)
                    ->first();

        if ($result && $result->grandTotal > 0) {
            return [
                'total' => number_format($result->grandTotal / $result->total, 1),
                'totalNo' => $result->total
            ];
        } else {
            return [
                'total' => 1,
                'totalNo' => 1
            ];
        }
    }
}
