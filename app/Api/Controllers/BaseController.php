<?php

namespace Api\Controllers;

use Api\Model\Company;
use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BaseController extends Controller
{
    use Helpers;

    protected $request='';

    public function __Construct(Request $request){
/*        $email   = $request->only('LoggedEmailAddress');
        $userID  = $request->only('LoggedUserID');
        if(empty($email['LoggedEmailAddress']) && empty($userID['LoggedUserID']))
        {
          $this->middleware('jwt.auth');
          $this->middleware('DefaultSettingLoad');
        }*/
    }
}