<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Storage;
class Blogs extends Model
{
    use HasFactory;
    public static $lang='en';
    protected $table='blog_posts';

   public static function getBlogsList($request){
    $language=$request->language;
    $category=$request->category;

    $perPage = $request->input('per_page', 10);
    $ishindi=false;
    if(!empty($request->lang) && $request->lang=='hi'){
        $language=2; // for hindi
    }
    $search=$request->search;
 
    $list=self::select(
        'blog_posts.id',
        'blog_posts.title',
        'blog_posts.media_file',
        'blog_posts.file_path',
        'blog_posts.popular_hashtags',
        'blog_posts.slug',
        'blog_posts.short_description',
        'blog_posts.total_likes',
        'blog_posts.total_views',
        'blog_posts.created_at',
    )
    ->join('blog_category_maps','blog_category_maps.blog_id','=','blog_posts.id')

    ->where('blog_posts.status',1)
    ->when($language, function ($query, $language) {
        $query->where('blog_posts.language',$language);
    })
    ->when($category, function ($query, $category) {
        $query->where('blog_category_maps.category_id',$category);
    })
    ->when($search, function ($query, $search) {
        $query->where('blog_posts.title', 'LIKE', "%{$search}%");
    })
    ->orderBy('blog_posts.created_at','desc')
    
    ->paginate($perPage)->map(function($item){
        if(!empty($item->media_file)){
			$item->full_image = image_url($item->media_file,'/public/cms-images/blogs/');
        }
        return $item;

    });;
    return $list;
   } 
}