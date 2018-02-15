<?php

namespace App\Http\Middleware;

use Api\Model\Company;
use Api\Model\Customer;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions;

class DBSelector
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


        $credentials = $request->only('LoggedEmailAddress', 'password');
        $UserID = $request->only('LoggedUserID');
        $LicenceKey  = $request->only('LicenceKey','CompanyName');
		$LoginType	=	$request->only('LoginType');
		
		/* if(isset($LoginType) && $LoginType['LoginType']=='customer') {
    	    Config::set('auth.model', '\Api\Model\Customer');
	        Config::set('auth.table', 'tblAccount');
			Config::set('jwt.identifier', 'AccountID');
 			Config::set('jwt.user', '\Api\Model\Customer');
			Config::set('auth.providers.users.model', \Api\Model\Customer::class);				  
			Config::set('auth.providers.users.table', 'tblAccount');
        }
		*/
        if (!empty($LicenceKey['LicenceKey']) && !empty($LicenceKey['CompanyName'])) {
            if(!empty($credentials['LoggedEmailAddress']) || !empty($UserID['LoggedUserID'])){
                $license = 	Company::getLicenceResponse($request);
                if($license['Status']!=1) {
                    Log::info("DBSelector Error");
                    Log::info($license);
                    return response()->json(['error' => $license['Message']], 401);
                }
            }
            $LICENSE_JSON = getenv($LicenceKey['LicenceKey']);
            if(!empty($LICENSE_JSON)) {

                if(isset($_COOKIE["customer_language"])){
                    App::setLocale($request->input('Language'));
                }else{
                    App::setLocale("en");
                }

                $LICENSE_ARRAY = json_decode($LICENSE_JSON, true);
                $DBSetting = $LICENSE_ARRAY[$LicenceKey['CompanyName']];

                //Log::info(print_r($DBSetting,true));

                if(!empty($DBSetting)) {


                    Config::set('database.connections.rm_db.host',     $DBSetting['RMDB']['DB_HOST']);
                    Config::set('database.connections.rm_db.username', $DBSetting['RMDB']['DB_USERNAME']);
                    Config::set('database.connections.rm_db.password', substr($DBSetting['RMDB']['DB_PASSWORD'],5));
                    Config::set('database.connections.rm_db.database', $DBSetting['RMDB']['DB_DATABASE']);

                    Config::set('database.connections.billing_db.host',     $DBSetting['BILLINGDB']['DB_HOST']);
                    Config::set('database.connections.billing_db.username', $DBSetting['BILLINGDB']['DB_USERNAME']);
                    Config::set('database.connections.billing_db.password', substr($DBSetting['BILLINGDB']['DB_PASSWORD'],5));
                    Config::set('database.connections.billing_db.database', $DBSetting['BILLINGDB']['DB_DATABASE']);

                    Config::set('database.connections.cdr_db.host',     $DBSetting['CDRDB']['DB_HOST']);
                    Config::set('database.connections.cdr_db.username', $DBSetting['CDRDB']['DB_USERNAME']);
                    Config::set('database.connections.cdr_db.password', substr($DBSetting['CDRDB']['DB_PASSWORD'],5));
                    Config::set('database.connections.cdr_db.database', $DBSetting['CDRDB']['DB_DATABASE']);

                    Config::set('database.connections.neon_report.host',     $DBSetting['REPORTDB']['DB_HOST']);
                    Config::set('database.connections.neon_report.username', $DBSetting['REPORTDB']['DB_USERNAME']);
                    Config::set('database.connections.neon_report.password', substr($DBSetting['REPORTDB']['DB_PASSWORD'],5));
                    Config::set('database.connections.neon_report.database', $DBSetting['REPORTDB']['DB_DATABASE']);

                }else{
                    return response()->json(['Company not found'], 404);
                }
            }else{
                return response()->json(['API DB Selection not configured'], 404);
            }
        }else{
            return response()->json(['Provide License Key and CompanyName for DB Selection'], 404);
        }

        $response = $next($request);
        return $response;
    }
}
