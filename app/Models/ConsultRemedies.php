<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultRemedies extends Model
{
    use HasFactory;
    protected $table='consult_remedies';
    protected $fillable = ['consult_it', 'remedies', 'pooja']; // Define mass assignable fields
    protected $casts = [
        'remedies' => 'array', // Automatically converts JSON to array & vice versa
        'pooja' => 'array' // Also cast 'pooja' as array
    ];
    
}
