<?php

namespace App\Http\Controllers;

use App\Models\FAQ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FAQController extends Controller
{
    //

    public function index(Request $request)
    {
        try {
            $data = FAQ::index();
            return ApiResponse(200,true,'success',$data);
        } catch (\Throwable $th) {
           return InternalError($th->getMessage());
        } 
    }

    public function show($id)
    {
        try {
            $faq = FAQ::show($id);
            return $faq;
        } catch (\Throwable $th) {
           return InternalError($th->getMessage());
        } 
    }

    public function destroy($id)
    {
        try {
            $faq = FAQ::destroy($id);
            return $faq;
        } catch (\Throwable $th) {
           return InternalError($th->getMessage());
        } 
    }


    
    public function store(Request $request)
    {
       try {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'answer' => 'required|string',

        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        $faq = FAQ::store($request);
        return $faq;

       } catch (\Throwable $th) {
       return InternalError($th->getMessage());
       }
       
    }


    public function update(Request $request,$id)
    {
       try {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'answer' => 'required|string',

        ]);
        if ($validator->fails()) {
            return errorResponse($validator->errors());
        }
        $faq = FAQ::updateRecord($request,$id);
        return $faq;

       } catch (\Throwable $th) {
       return InternalError($th->getMessage());
       }
       
    }


}
