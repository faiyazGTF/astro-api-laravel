<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowModel extends Model
{
    use HasFactory;
    protected $table='tbl_follow';


    // public function following()
    // {
    //     return $this->hasOne(User::class, 'id','expert_id');
    // }

}
