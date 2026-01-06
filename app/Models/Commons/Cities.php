<?php

namespace App\Models\Commons;

use App\Models\User\UsersDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cities extends Model
{
    use HasFactory;

    protected $table = 'mst_cities';
    protected $fillable = ['city_name', 'state_id'];
    public $timestamps=false;
   

    public function users()
    {
        return $this->hasMany(UsersDetail::class, 'city');
    }


}
