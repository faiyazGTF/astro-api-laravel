<?php

namespace App\Models\Commons;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
  use HasFactory;
    public $timestamps=false;

    protected $table = 'mst_states';
    protected $fillable = ['state_name', 'state_name'];

   
}
