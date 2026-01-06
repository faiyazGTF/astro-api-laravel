<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class PoojaCategory extends Model
{
    use HasFactory; 
    protected static $lang='en';
    protected $table='product_categories';

    public static function getPoojaCategoty($request){
        $perPage = $request->input('per_page', 10);
        
        if(!empty($request->lang) && $request->lang=='hi'){
         self::$lang='hi';
        }
        $data= self::where('category_type', 'Service')
        ->where('name', 'like', '%' . "Pooja" . '%')
        ->orwhere('name', 'like', '%' . "Deity" . '%')
        ->orwhere('name', 'like', '%' . "Yagyas" . '%')
        ->orwhere('name', 'like', '%' . "Sahasranam" . '%')
        ->orwhere('name', 'like', '%' . "Enemy" . '%')
        ->orwhere('name', 'like', '%' . "Anushthan" . '%')
        ->orwhere('name', 'like', '%' . "puja" . '%')
        ->orderby('name', 'ASC')
        ->orderBy('sort_order', 'ASC')
        ->paginate($perPage);
      
        $data->transform(function ($category) {

           $category->full_image_url =image_url($category->image,$category->image_path);
       //    $category->full_image_url2 = image_url('', $category->image_path, $category->image);
       
           if(self::$lang=='hi' && !empty($category->name_hindi)){
            $category->name=$category->name_hindi;
           }
           if(self::$lang=='hi' && !empty($category->description_hindi)){
            $category->description=$category->description_hindi;
           }

            return $category;
        });


        
        return $data;
    }

    

   // public function getFullImageUrlAttribute()
   // {
   //     return asset($this->image_path . $this->image);
   // }
	
	public function getFullImageUrlAttribute()
	{
        return image_url($this->image,$this->image_path);
	}

    
}
