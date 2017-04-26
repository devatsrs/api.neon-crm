<?php

namespace Api\Controllers;

use Api\Model\Company;
use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\Auth;

class BaseController extends Controller
{
    use Helpers;

    protected $request='';

    public function __Construct(Request $request){
        $this->middleware('jwt.auth');
        $this->middleware('DefaultSettingLoad');
    }
}