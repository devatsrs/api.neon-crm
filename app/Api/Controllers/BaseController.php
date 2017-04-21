<?php

namespace Api\Controllers;

use Api\Model\Company;
use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller;
use Dingo\Api\Http\Request;

class BaseController extends Controller
{
    use Helpers;

    protected $request='';

    public function __Construct(Request $request){
        $this->request = $request;

        if(isset(Auth::user()->CompanyID)){

            $Timezone = Company::getCompanyTimeZone(0);
            if (isset($Timezone) && $Timezone != '') {
                date_default_timezone_set($Timezone);
                Config::set('app.timezone',$Timezone);
            }
        }
    }
}