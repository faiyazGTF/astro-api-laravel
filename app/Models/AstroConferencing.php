<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AstroConferencing extends Model
{
    use HasFactory;
    protected $table='astro_conferencing';

    protected $fillable = [
        'astro_id',
        'roomid',
        'token',
        'is_available',
    ];
}
