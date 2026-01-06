<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\returnSelf;

class NoticeBoard extends Model
{
    use HasFactory;

    protected $table = 'notice_board'; // Specify the table name if different
    protected $fillable = ['usertype', 'heading', 'description','image']; // Define mass assignable fields


    public static function index()
    {
        $faqs = self::all();
        $faqs->transform(function ($faq) {

        if (!empty($faq->image)) {
         $faq->image=image_url($faq->image);
        } else {
            $faq->presigned_url = null;
        }

        return $faq;
    });
    
        return $faqs;
    }

    public static function store($request)
    {
        try {
            $obj = new NoticeBoard();
            if ($request->hasFile('image')) {
                $imageName = 'notice-board-' . time() . '.' . $request->image->getClientOriginalExtension();
                 Storage::disk('s3')->putFileAs('public/cms-images/notice-boards', $request->file('image'),$imageName);
                 $obj->image = '/public/cms-images/notice-boards'. '/' . $imageName;
            }
            $obj->usertype = $request->usertype;
            $obj->heading = $request->heading;
            $obj->description = $request->description;
            $obj->save();
    
            return ApiResponse(200, true, 'Notice Added Successfully', $obj);
        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }

    public static function updateRecord($request,$id)
    {
    
     
        try {
            $obj = NoticeBoard::find($id);
            if($obj){

                if ($request->hasFile('image')) {
                    if ($obj->image) {
                        $imagePath = public_path('cms-images/notice-boards/' . basename($obj->image));
                       
                        if (file_exists($imagePath)) {
                            unlink($imagePath); // Deletes the file
                        }
                    }
                    $imageName = 'notice-board-' . time() . '.' . $request->image->getClientOriginalExtension();
                     Storage::disk('s3')->putFileAs('public/cms-images/notice-boards', $request->file('image'),$imageName);
                     $obj->image = '/public/cms-images/notice-boards'. '/' . $imageName;
                }
                $obj->usertype = $request->usertype;
                $obj->heading = $request->heading;
                $obj->description = $request->description;
                $obj->save();
                return ApiResponse(200, true, 'Notice Added Successfully', $obj);
            }
            return SimpleResponse(404,false,'Not found');

        } catch (\Throwable $th) {
            return InternalError($th->getMessage());
        }
    }


    public static function show($id)
    {
        $obj = NoticeBoard::findOrFail($id);
        if($obj)
        {
            return ApiResponse(200,true,'success',$obj);
        }  
        return ApiResponse(404,false,'not found');
    }


    public static function destroy($id)
    {
        $obj = NoticeBoard::findOrFail($id);

        if ($obj->image) {
            $imagePath = public_path('cms-images/notice-boards/' . basename($obj->image));
           
            if (file_exists($imagePath)) {
                unlink($imagePath); // Deletes the file
            }
        }


        if($obj->delete()){
            return ApiResponse(200,true,'success',$obj);
        }
        return ApiResponse(404,false,'not found');
    }




}
