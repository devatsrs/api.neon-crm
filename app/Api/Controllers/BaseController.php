<?php

namespace Api\Controllers;

use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller;
use Dingo\Api\Http\Request;

class BaseController extends Controller
{
    use Helpers;

    protected $request='';

    public function __Construct(Request $request){
        $this->request = $request;
    }
}