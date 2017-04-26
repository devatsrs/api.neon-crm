<?php

namespace App\Http\Middleware;

use Api\Model\User;
use Api\Model\Company;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        Log::info(date('Y-m-d H:i:s'));
        $Timezone = Company::getCompanyTimeZone(User::get_companyID());
        if (isset($Timezone) && $Timezone != '') {
            date_default_timezone_set($Timezone);
            Config::set('app.timezone',$Timezone);
        }
        Log::info(date('Y-m-d H:i:s'));
		 $response = $next($request);
        return $response;

	}
}
