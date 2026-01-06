<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialisationModel extends Model
{
    use HasFactory;
    protected $table = 'mst_specialisation'; 
    public $timestamps = false;
}
