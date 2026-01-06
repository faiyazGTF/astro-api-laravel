<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SwitchSession extends Model
{
    use HasFactory;
    protected $table ='switch_session';

    public $fillable = [
        'session_id', // Add this field to allow mass assignment,
        'switch_to'
    ];
}
