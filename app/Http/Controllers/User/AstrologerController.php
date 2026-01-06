<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User\UsersDetail;
class AstrologerController extends Controller
{
    //

    public  function getChatAstroList(Request $request,$type=""){
        $response=UsersDetail::getChatAstroList($request,$type);
        return $response;
    }
    public  function getAstroProfile(Request $request,$astroId){

        $response=UsersDetail::getAstroProfile($request,$astroId);
         return $response;
    }
    
}
