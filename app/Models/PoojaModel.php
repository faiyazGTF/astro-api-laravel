<?php

namespace App\Models;

use App\Models\User\ReviewsModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class PoojaModel extends Model
{


    protected static $lang='en';

    public function ProductVariant()
    {
        return $this->hasMany(ProductVarient::class, 'product_id');
    }
    public function Reviews()
    {
        return $this->hasMany(ReviewsModel::class, 'product_id')->with('User');
    }

    use HasFactory;
    protected $table='products';
    public static function getPoojaList($request){

        if(!empty($request->lang) && $request->lang=='hi'){
            self::$lang='hi';
           }



        $search=$request->search;
        $catId=$request->catId;
        $perPage = $request->input('per_page', 10);
        $list=self::select('products.*', 'pc.name as category_name')
        ->join('product_category_maps as pcm', 'pcm.product_id', '=', 'products.id')
        ->join('product_categories as pc', 'pc.id', '=', 'pcm.category_id')
        
        ->where('products.status', 1)
			 ->where('products.product_type', 'Service')
        ->when($search, function ($query, $search) {
            $query->where('products.name', 'like', '%' . $search . '%');
        })
        ->when($catId, function ($query, $catId) {
            $query->where('pcm.category_id',$catId);
        })
        
        ->paginate($perPage);
        $list->getCollection()->transform(function ($item) {
            if ($item->image != '') {
                
                $item->image_url =image_url($item->image,$item->image_path);
            } else {
                
                $item->image_url = asset('cms-images/default/default.jpg');
            }


            if(self::$lang=='hi' && !empty($item->name_hindi)){
                $item->name=$item->name_hindi;
               }
               if(self::$lang=='hi' && !empty($item->description_hindi)){
                $item->description=$item->description_hindi;
               }
    
            return $item;
        });
        return $list;
    }


    
    public static function getPoojaDetails($poojaId){

        $pooja = PoojaModel::where('products.status', '=', 1)
        ->join('product_categories', 'product_categories.id', '=', 'products.category_ids')
        ->leftJoin('review', 'review.product_id', '=', 'products.id')
			->leftJoin('taxes', 'taxes.id', '=', 'products.tax_id')
        ->where('products.id', $poojaId)
        ->select(
            'products.id',
			'taxes.tax_value',
			'taxes.tax_name',
            'products.name',
            'products.slug',
            'products.sku',
            'products.product_type',
            'products.status',
            'products.quantity',
            'products.qty_sold',
            'products.stock_status_id',
            'products.offline_rating',
            'products.is_offline_rating',
            'products.price_inr',
            'products.discount_price_inr',
            'products.shipping_charge',
            'products.tax_id',
            'products.date_available',
            'product_categories.name as product_categories_name',
            'products.isVariantProduct',
            'products.short_description',
            'products.description',
            'products.shipping_details',
            'products.image',
            'products.image_path',
            'products.other_images',
            'products.image_alt_title',
            'products.video',
            'products.popular_hashtags',
            'products.meta_title',
            'products.meta_description',
            'products.meta_keyword',
            'products.manufacturer_id',
            'products.location',
            'products.price_usd',
            'products.discount_price_usd',
            'products.location',
            'products.referral_percent',
            'products.name_hindi',
            'products.description_hindi',
            'products.shipping_details_hindi',
            'products.weight_class_id',
            'products.sort_order',
            'products.benefit',
            'products.batch_img',
            'products.one_line'
        )
        ->first();
    

        $reviews = ReviewsModel::where('product_id', $poojaId)->get();

        $totalRating = $reviews->count();  // Total number of reviews
        $avgRating = $reviews->avg('rating');  // Average rating

        if($pooja){
            $pooja->popular_hashtags=explodesTag($pooja->popular_hashtags);
            $pooja->total_rating=$totalRating;
            $pooja->avg_rating=$avgRating;
            
            $pooja->full_image=image_url($pooja->image,$pooja->image_path);



            if(self::$lang=='hi' && !empty($item->name_hindi)){
                $pooja->name=$pooja->name_hindi;

            }
            if(self::$lang=='hi' && !empty($item->description_hindi)){
                $pooja->description=$pooja->description_hindi;

            }
            $otherpooja=[];
         
            if(!empty($pooja->other_images)){
                $decodeotherimage=json_decode($pooja->other_images);
                for ($i=0; $i <count($decodeotherimage) ; $i++) { 
                    
                    $otherpooja[]=image_url($decodeotherimage[$i],$pooja->image_path);

                }
            }
            $pooja->other_images=$otherpooja;
            return $pooja;
        }
        return response()->json([
            'statusCode'=>404,
            'status'=>false,
            'message'=>'not found',
        ]);


    }
    public static function getHomePagePooja(){
        $poojas = DB::table('checkouts as c')
        ->select('p.id')
        ->join('products as p', 'c.user_kundali_request_info_id', '=', 'p.id')
        ->where('p.product_type', 'Service')
        ->where('c.product_type', 'product')
        ->groupBy('c.user_kundali_request_info_id', 'p.id') // Add p.id to GROUP BY
        ->orderByRaw('COUNT(c.id) DESC')
        ->limit(5)
        ->pluck('p.id')
        ->toArray();
        
        $poojalist = PoojaModel::select('id', 'name', 'slug', 'sku', 'price_inr', 'discount_price_inr', 'image', 'image_path')
        ->whereIn('id', $poojas)
        ->take(5)
        ->get()
        ->map(function ($item) {
            if (!empty($item->image_path)) {
                $item->full_image = image_url($item->image,$item->image_path);
            }
            return $item;
        });

    
        
        return $poojalist;
    }
}
