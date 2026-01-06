<?php

namespace App\Models\Commons;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Countries extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'mst_countries';
    protected $fillable = [
        'sortname',
        'country_name',
        'phonecode',
        'default_status',
        'status',
        'active_status',
        'cont_digits',
    ];

}
