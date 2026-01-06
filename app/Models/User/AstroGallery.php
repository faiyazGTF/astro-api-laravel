<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AstroGallery extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'image', 'image_path'];

    // public function getFullImageUrlAttribute()
    // {
    //     return asset($this->image_path.$this->user_id.'/' . $this->image);
    // }
    
}
