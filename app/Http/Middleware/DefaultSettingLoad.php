<?php

namespace App\Http\Middleware;

use Api\Model\User;
use Api\Model\Company;
use Api\Model\Customer;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class DefaultSettingLoad
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     */


	 
    public function handle($request, Closure $next){
        $Timezone = Company::getCompanyTimeZone(Auth::user()->CompanyID);
        if (isset($Timezone) && $Timezone != '') {
            date_default_timezone_set($Timezone);
            Config::set('app.timezone',$Timezone);
        }
		 $response = $next($request);
        return $response;

	}
}
