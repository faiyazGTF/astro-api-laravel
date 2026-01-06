<?php

namespace App\Http\Controllers;

use App\Models\FAQ;
use App\Models\NoticeBoard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NoticeBoardController extends Controller
{
    //

    public function index(Request $request)
    {
        try {
            $data = NoticeBoard::index();
            return ApiResponse(200,true,'success',$data);
        } catch (\Throwable $th) {
           return InternalError($th->getMessage());
        } 
    }

    public function show($id)
    {
        try {
            $data = NoticeBoard::show($id);
            return $data;
        } catch (\Throwable $th) {
           return InternalError($th->getMessage());
        } 
    }

    public function destroy($id)
    {
        try {
            $data = NoticeBoard::destroy($id);
            return $data;
        } catch (\Throwable $th) {
           return InternalError($th->getMessage());
        } 
    }


    
    public function store(Request $request)
    {
       try {
        $validator = Validator::make($request->all(), [
            'usertype' => 'required|string|in:USER,ASTROLOGER',
            'heading' => 'required|string',
            'description' => 'required|string',
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }

        
        $data = NoticeBoard::store($request);
        return ApiResponse(200,true,'success',$data);

     

       } catch (\Throwable $th) {
       return InternalError($th->getMessage());
       }
       
    }


    public function update(Request $request,$id)
    {
       try {
        $validator = Validator::make($request->all(), [
            'usertype' => 'required|string|in:USER,ASTROLOGER',
            'heading' => 'required|string',
            'description' => 'required|string',
        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        $result = NoticeBoard::updateRecord($request,$id);
        return ApiResponse(200,true,'success',$result);


       } catch (\Throwable $th) {
       return InternalError($th->getMessage());
       }
       
    }


}
