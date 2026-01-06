<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FAQ extends Model
{
    use HasFactory;

    protected $table = 'faq'; // Specify the table name if different
    protected $fillable = ['question', 'answer', 'status']; // Define mass assignable fields


    public static function index()
    {
        $faqs = self::all();
        return $faqs;
    }

    public static function store($request)
    {
        $faq = FAQ::create($request->all());
        if($faq->save())
        {
            return ApiResponse(200,true,'success',$faq);
        }  
       return InternalError('something went wrong');
    }

    public static function updateRecord($request,$id)
    {
        $faq = FAQ::findOrFail($id);
       
        if($faq->update($request->all()))
        {
            return ApiResponse(200,true,'success',$faq);
        }  
       return InternalError('something went wrong');
    }


    public static function show($id)
    {
        $faq = FAQ::findOrFail($id);
        if($faq)
        {
            return ApiResponse(200,true,'success',$faq);
        }  
        return ApiResponse(404,false,'not found');
    }


    public static function destroy($id)
    {
        $faq = FAQ::findOrFail($id);
        if($faq->delete()){
            return ApiResponse(200,true,'success',$faq);
        }
        return ApiResponse(404,false,'not found');
    }




}
