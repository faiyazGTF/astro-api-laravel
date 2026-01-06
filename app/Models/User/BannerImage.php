<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;
class BannerImage extends Model
{
    use HasFactory;
    protected $table='banner_images';

    public function getFullImageUrlAttribute()
    {
        return image_url($this->image,$this->image_path.$this->user_id.'/');
    }
    
}
