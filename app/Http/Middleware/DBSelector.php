<?php

namespace App\Http\Middleware;

use Api\Model\Company;
use Carbon\Carbon;
use Closure;
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
        $LicenceKey['LicenceHost'] = $request->getHttpHost();
        $LicenceKey['LicenceIP'] = $request->getClientIp();
        if (!empty($LicenceKey['LicenceKey']) || !empty($LicenceKey['CompanyName'])) {
            if(!empty($credentials['LoggedEmailAddress']) || !empty($UserID['LoggedUserID'])){
                $license = 	Company::getLicenceResponse($LicenceKey);
                if($license['Status']!=1) {
                    return response()->json(['error' => $license['Message']], 401);
                }
            }
            $LICENSE_JSON = getenv($LicenceKey['LicenceKey']);
            if(!empty($LICENSE_JSON)) {
                $LICENSE_ARRAY = json_decode($LICENSE_JSON, true);
                $DBSetting = $LICENSE_ARRAY[$LicenceKey['CompanyName']];
                if(!empty($DBSetting)) {
                    Config::set('database.connections.mysql.username', $DBSetting['RMDB']['DB_USERNAME']);
                    Config::set('database.connections.mysql.password', $DBSetting['RMDB']['DB_PASSWORD']);
                    Config::set('database.connections.mysql.database', $DBSetting['RMDB']['DB_DATABASE']);
                }else{
                    return response()->json(['Company not found'], 404);
                }
            }else{
                return response()->json(['Not Configured'], 404);
            }
        }else{
            return response()->json(['Provide License Key and CompanyName'], 404);
        }

        $response = $next($request);
        return $response;
    }
}
